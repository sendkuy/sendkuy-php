<?php

namespace SendKuy\Tests\Support;

use GuzzleHttp\Psr7\Response;
use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class FakeClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param  list<ResponseInterface|Throwable>  $queue
     */
    public function __construct(private array $queue = []) {}

    /**
     * @param  array<array-key, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public static function json(int $status, array $body, array $headers = []): Response
    {
        return new Response($status, ['Content-Type' => 'application/json', ...$headers], json_encode($body, JSON_THROW_ON_ERROR));
    }

    public function push(ResponseInterface|Throwable ...$responses): self
    {
        foreach ($responses as $response) {
            $this->queue[] = $response;
        }

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue) ?? throw new LogicException('No response queued for '.$request->getMethod().' '.$request->getUri());

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function body(int $index = 0): array
    {
        $decoded = json_decode((string) $this->requests[$index]->getBody(), true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
