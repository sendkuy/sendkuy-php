<?php

namespace SendKuy;

use Psr\Http\Message\ResponseInterface;
use SendKuy\Exceptions\ApiException;
use SendKuy\Exceptions\AuthenticationException;
use SendKuy\Exceptions\AuthorizationException;
use SendKuy\Exceptions\InvalidRequestException;
use SendKuy\Exceptions\RateLimitException;
use SendKuy\Exceptions\SendKuyException;

final class ErrorFactory
{
    private const BY_TYPE = [
        'authentication_error' => AuthenticationException::class,
        'authorization_error' => AuthorizationException::class,
        'invalid_request_error' => InvalidRequestException::class,
        'rate_limit_error' => RateLimitException::class,
        'api_error' => ApiException::class,
    ];

    public static function fromResponse(ResponseInterface $response): SendKuyException
    {
        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $decoded = json_decode($raw, true);
        $error = is_array($decoded) && is_array($decoded['error'] ?? null) ? $decoded['error'] : null;
        $requestId = $response->getHeaderLine('X-Request-Id');
        $requestId = $requestId === '' ? null : $requestId;
        $retryAfter = preg_match('/^\d+$/', trim($response->getHeaderLine('Retry-After'))) === 1
            ? (int) trim($response->getHeaderLine('Retry-After'))
            : null;

        if ($error === null) {
            $snippet = substr(trim($raw), 0, 200);

            while ($snippet !== '' && preg_match('//u', $snippet) !== 1) {
                $snippet = substr($snippet, 0, -1);
            }
            $class = self::byStatus($status);

            return new $class(
                'HTTP '.$status.($snippet === '' ? '' : ': '.$snippet),
                httpStatus: $status,
                requestId: $requestId,
                retryAfter: $retryAfter,
            );
        }

        $type = self::text($error['type'] ?? null);
        $class = $type !== null ? (self::BY_TYPE[$type] ?? SendKuyException::class) : SendKuyException::class;

        return new $class(
            self::text($error['message'] ?? null) ?? 'HTTP '.$status,
            httpStatus: $status,
            errorType: $type,
            errorCode: self::text($error['code'] ?? null),
            param: self::text($error['param'] ?? null),
            docUrl: self::text($error['doc_url'] ?? null),
            requestId: $requestId,
            retryAfter: $retryAfter,
        );
    }

    /**
     * @return class-string<SendKuyException>
     */
    private static function byStatus(int $status): string
    {
        return match (true) {
            $status === 401 => AuthenticationException::class,
            $status === 403 => AuthorizationException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ApiException::class,
            default => InvalidRequestException::class,
        };
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
