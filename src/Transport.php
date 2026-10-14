<?php

namespace SendKuy;

use Closure;
use DateTimeInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SendKuy\Exceptions\ApiException;
use SendKuy\Exceptions\ConnectionException;
use SendKuy\Exceptions\SendKuyException;
use stdClass;

final class Transport
{
    private const WRITES = ['POST', 'PUT', 'PATCH', 'DELETE'];

    private const MAX_RETRY_AFTER = 60;

    private readonly string $baseUrl;

    private readonly float $timeout;

    private readonly int $maxRetries;

    private readonly ?ClientInterface $client;

    private readonly Closure $sleep;

    private readonly string $userAgent;

    /** @var array<array-key, ClientInterface> */
    private array $clients = [];

    /**
     * @param  array{base_url?: string, timeout?: float|int, max_retries?: int, http_client?: ClientInterface, sleep?: callable(float): void}  $options
     */
    public function __construct(#[\SensitiveParameter] private readonly string $apiKey, array $options = [])
    {
        $this->baseUrl = rtrim($options['base_url'] ?? SendKuy::DEFAULT_BASE_URL, '/');
        $this->timeout = (float) ($options['timeout'] ?? 30);
        $this->maxRetries = max(0, $options['max_retries'] ?? 2);
        $this->client = $options['http_client'] ?? null;
        $this->sleep = Closure::fromCallable($options['sleep'] ?? static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        });
        $this->userAgent = 'sendkuy-php/'.SendKuy::VERSION.' PHP/'.PHP_VERSION;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @param  array{idempotency_key?: string, timeout?: float|int}  $options
     * @return array<array-key, mixed>
     *
     * @throws SendKuyException
     */
    public function send(string $method, string $path, array $query = [], ?array $body = null, array $options = []): array
    {
        $method = strtoupper($method);
        $key = $options['idempotency_key'] ?? (in_array($method, self::WRITES, true) ? self::uuid() : null);
        $client = $this->client((float) ($options['timeout'] ?? $this->timeout));

        try {
            $request = $this->request($method, $path, $query, $body, $key);
        } catch (JsonException|InvalidArgumentException $e) {
            throw new SendKuyException('Could not build the request to SendKuy: '.$e->getMessage(), previous: $e);
        }

        for ($attempt = 0; ; $attempt++) {
            try {
                $response = $client->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                if ($attempt < $this->maxRetries) {
                    ($this->sleep)($this->backoff($attempt));

                    continue;
                }

                throw new ConnectionException('Could not reach SendKuy: '.$e->getMessage(), previous: $e);
            }

            if ($response->getStatusCode() < 400) {
                return $this->decode($response);
            }

            $error = ErrorFactory::fromResponse($response);
            $delay = $attempt < $this->maxRetries ? $this->delay($error, $attempt) : null;

            if ($delay === null) {
                throw $error;
            }

            ($this->sleep)($delay);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
            'api_key' => '[redacted]',
        ];
    }

    private function client(float $timeout): ClientInterface
    {
        return $this->client ?? ($this->clients[(string) $timeout] ??= new GuzzleClient(['timeout' => $timeout]));
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     */
    private function request(string $method, string $path, array $query, ?array $body, ?string $key): RequestInterface
    {
        $uri = $this->baseUrl.'/'.ltrim($path, '/');
        $query = self::query($query);

        if ($query !== []) {
            $uri .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
            'User-Agent' => $this->userAgent,
        ];

        if ($key !== null) {
            $headers['Idempotency-Key'] = $key;
        }

        if ($body === null) {
            return new Request($method, $uri, $headers);
        }

        $headers['Content-Type'] = 'application/json';
        $json = $body === []
            ? '{}'
            : json_encode(self::serialize($body), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new Request($method, $uri, $headers, $json);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $contents = (string) $response->getBody();

        if ($contents === '') {
            return [];
        }

        $decoded = json_decode($contents);

        if (! $decoded instanceof stdClass) {
            $requestId = $response->getHeaderLine('X-Request-Id');

            throw new ApiException(
                'SendKuy answered with a body that is not JSON.',
                httpStatus: $response->getStatusCode(),
                requestId: $requestId === '' ? null : $requestId,
            );
        }

        return get_object_vars($decoded);
    }

    private function delay(SendKuyException $error, int $attempt): ?float
    {
        $status = $error->getHttpStatus();

        if ($status === 429) {
            $after = $error->getRetryAfter();

            if ($after === null) {
                return $this->backoff($attempt);
            }

            return $after > self::MAX_RETRY_AFTER ? null : (float) $after;
        }

        if ($status >= 500 || ($status === 409 && $error->getErrorCode() === 'idempotency_request_in_progress')) {
            return $this->backoff($attempt);
        }

        return null;
    }

    private function backoff(int $attempt): float
    {
        return min(8.0, 0.5 * 2 ** $attempt) * (random_int(500, 1000) / 1000);
    }

    private static function serialize(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return is_array($value) ? array_map(self::serialize(...), $value) : $value;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private static function query(array $query): array
    {
        $normalized = [];

        foreach ($query as $name => $value) {
            if ($value === null) {
                continue;
            }

            $normalized[$name] = match (true) {
                $value === true => 'true',
                $value === false => 'false',
                $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
                default => $value,
            };
        }

        return $normalized;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
