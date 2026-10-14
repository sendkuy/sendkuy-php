<?php

namespace SendKuy\Tests;

use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SendKuy\Exceptions\ApiException;
use SendKuy\Exceptions\AuthenticationException;
use SendKuy\Exceptions\AuthorizationException;
use SendKuy\Exceptions\ConnectionException;
use SendKuy\Exceptions\InvalidRequestException;
use SendKuy\Exceptions\RateLimitException;
use SendKuy\Exceptions\SendKuyException;
use SendKuy\SendKuy;
use SendKuy\Tests\Support\FakeClient;
use SendKuy\Tests\Support\FakeNetworkException;
use SendKuy\Transport;

final class TransportTest extends TestCase
{
    /** @var list<float> */
    private array $sleeps = [];

    private function transport(FakeClient $client, int $maxRetries = 2, string $baseUrl = SendKuy::DEFAULT_BASE_URL): Transport
    {
        return new Transport('sk_test_123', [
            'base_url' => $baseUrl,
            'http_client' => $client,
            'max_retries' => $maxRetries,
            'sleep' => function (float $seconds): void {
                $this->sleeps[] = $seconds;
            },
        ]);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function error(int $status, string $type, string $code, array $headers = []): Response
    {
        return FakeClient::json($status, ['error' => [
            'type' => $type,
            'code' => $code,
            'message' => 'The field to is required.',
            'param' => 'to',
            'doc_url' => 'https://docs.sendkuy.com/errors#'.$code,
        ]], ['X-Request-Id' => 'req_123', ...$headers]);
    }

    public function test_sends_the_key_and_user_agent_to_a_custom_base_url(): void
    {
        $client = new FakeClient([FakeClient::json(200, ['data' => []])]);

        $this->transport($client, baseUrl: 'https://proxy.example.com/api/v1/')->send('GET', '/account');

        $request = $client->requests[0];
        $this->assertSame('https://proxy.example.com/api/v1/account', (string) $request->getUri());
        $this->assertSame('Bearer sk_test_123', $request->getHeaderLine('Authorization'));
        $this->assertStringStartsWith('sendkuy-php/'.SendKuy::VERSION.' PHP/', $request->getHeaderLine('User-Agent'));
    }

    public function test_retries_connection_and_server_errors_with_the_same_idempotency_key(): void
    {
        $client = new FakeClient([new FakeNetworkException, self::error(503, 'api_error', 'internal_error'), FakeClient::json(201, ['data' => []])]);

        $this->transport($client)->send('POST', '/messages', [], ['body' => 'Hi']);

        $this->assertCount(3, $client->requests);
        $key = $client->requests[0]->getHeaderLine('Idempotency-Key');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $key);
        $this->assertSame($key, $client->requests[1]->getHeaderLine('Idempotency-Key'));
        $this->assertSame($key, $client->requests[2]->getHeaderLine('Idempotency-Key'));
        $this->assertCount(2, $this->sleeps);
        $this->assertGreaterThanOrEqual(0.25, $this->sleeps[0]);
        $this->assertLessThanOrEqual(0.5, $this->sleeps[0]);
        $this->assertGreaterThanOrEqual(0.5, $this->sleeps[1]);
        $this->assertLessThanOrEqual(1.0, $this->sleeps[1]);
    }

    public function test_waits_for_retry_after_on_429(): void
    {
        $client = new FakeClient([self::error(429, 'rate_limit_error', 'rate_limit_exceeded', ['Retry-After' => '2']), FakeClient::json(200, ['data' => []])]);

        $this->transport($client)->send('GET', '/account');

        $this->assertSame([2.0], $this->sleeps);
    }

    public function test_throws_at_once_when_retry_after_is_over_a_minute(): void
    {
        $client = new FakeClient([self::error(429, 'rate_limit_error', 'rate_limit_exceeded', ['Retry-After' => '120'])]);

        try {
            $this->transport($client)->send('POST', '/verifications', [], ['to' => '+6281234567890']);
            $this->fail('Expected a RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(120, $e->getRetryAfter());
        }

        $this->assertCount(1, $client->requests);
        $this->assertSame([], $this->sleeps);
    }

    public function test_falls_back_to_backoff_when_retry_after_is_not_seconds(): void
    {
        $client = new FakeClient([
            self::error(429, 'rate_limit_error', 'rate_limit_exceeded', ['Retry-After' => 'Wed, 21 Oct 2026 07:28:00 GMT']),
            FakeClient::json(200, ['data' => []]),
        ]);

        $this->transport($client)->send('GET', '/account');

        $this->assertCount(1, $this->sleeps);
        $this->assertGreaterThanOrEqual(0.25, $this->sleeps[0]);
        $this->assertLessThanOrEqual(0.5, $this->sleeps[0]);
    }

    public function test_retries_a_request_still_in_progress(): void
    {
        $client = new FakeClient([
            self::error(409, 'invalid_request_error', 'idempotency_request_in_progress'),
            FakeClient::json(201, ['data' => []]),
        ]);

        $this->transport($client)->send('POST', '/messages', [], ['body' => 'Hi'], ['idempotency_key' => 'order-1042']);

        $this->assertCount(2, $client->requests);
    }

    public function test_gives_up_after_max_retries(): void
    {
        $client = new FakeClient(array_fill(0, 3, self::error(503, 'api_error', 'internal_error')));

        $this->expectException(ApiException::class);

        try {
            $this->transport($client)->send('GET', '/account');
        } finally {
            $this->assertCount(3, $client->requests);
        }
    }

    public function test_does_not_retry_other_client_errors_and_keeps_every_field(): void
    {
        $client = new FakeClient([self::error(422, 'invalid_request_error', 'validation_failed')]);

        try {
            $this->transport($client)->send('POST', '/messages', [], ['channel' => 'whatsapp']);
            $this->fail('Expected an InvalidRequestException');
        } catch (InvalidRequestException $e) {
            $this->assertSame('validation_failed', $e->getErrorCode());
            $this->assertSame('invalid_request_error', $e->getErrorType());
            $this->assertSame('to', $e->getParam());
            $this->assertSame('https://docs.sendkuy.com/errors#validation_failed', $e->getDocUrl());
            $this->assertSame('req_123', $e->getRequestId());
            $this->assertSame(422, $e->getHttpStatus());
            $this->assertSame(422, $e->getCode());
            $this->assertSame('The field to is required.', $e->getMessage());
        }

        $this->assertCount(1, $client->requests);
    }

    public function test_uses_the_given_idempotency_key_and_none_for_reads(): void
    {
        $client = new FakeClient([FakeClient::json(201, ['data' => []]), FakeClient::json(200, ['data' => []])]);
        $transport = $this->transport($client);

        $transport->send('POST', '/messages', [], ['body' => 'Hi'], ['idempotency_key' => 'order-1042']);
        $transport->send('GET', '/messages');

        $this->assertSame('order-1042', $client->requests[0]->getHeaderLine('Idempotency-Key'));
        $this->assertFalse($client->requests[1]->hasHeader('Idempotency-Key'));
    }

    /**
     * @return iterable<string, array{string, class-string<SendKuyException>}>
     */
    public static function errorTypes(): iterable
    {
        yield 'authentication_error' => ['authentication_error', AuthenticationException::class];
        yield 'authorization_error' => ['authorization_error', AuthorizationException::class];
        yield 'invalid_request_error' => ['invalid_request_error', InvalidRequestException::class];
        yield 'rate_limit_error' => ['rate_limit_error', RateLimitException::class];
        yield 'api_error' => ['api_error', ApiException::class];
        yield 'a type this SDK does not know yet' => ['quota_error', SendKuyException::class];
    }

    /**
     * @param  class-string<SendKuyException>  $class
     */
    #[DataProvider('errorTypes')]
    public function test_picks_the_exception_from_the_error_type(string $type, string $class): void
    {
        $client = new FakeClient([self::error(400, $type, 'request_refused')]);

        try {
            $this->transport($client, maxRetries: 0)->send('GET', '/account');
            $this->fail('Expected an exception');
        } catch (SendKuyException $e) {
            $this->assertSame($class, $e::class);
        }
    }

    public function test_maps_a_failure_without_an_error_envelope_by_status(): void
    {
        $client = new FakeClient([new Response(502, ['Content-Type' => 'text/html'], '<html>Bad gateway</html>')]);

        try {
            $this->transport($client, maxRetries: 0)->send('GET', '/account');
            $this->fail('Expected an ApiException');
        } catch (ApiException $e) {
            $this->assertNull($e->getErrorCode());
            $this->assertSame('HTTP 502: <html>Bad gateway</html>', $e->getMessage());
        }
    }

    public function test_serializes_dates_booleans_and_nulls(): void
    {
        $client = new FakeClient([FakeClient::json(201, ['data' => []]), FakeClient::json(200, ['data' => []])]);
        $transport = $this->transport($client);
        $at = new DateTimeImmutable('2026-10-14 10:00:00', new DateTimeZone('+07:00'));

        $transport->send('POST', '/messages', [], ['body' => 'Hi', 'schedule_at' => $at, 'media' => ['taken_at' => $at]]);
        $transport->send('GET', '/messages', ['created_after' => $at, 'flag' => true, 'missing' => null]);

        $this->assertSame(['body' => 'Hi', 'schedule_at' => '2026-10-14T10:00:00+07:00', 'media' => ['taken_at' => '2026-10-14T10:00:00+07:00']], $client->body(0));
        parse_str($client->requests[1]->getUri()->getQuery(), $query);
        $this->assertSame(['created_after' => '2026-10-14T10:00:00+07:00', 'flag' => 'true'], $query);
    }

    public function test_a_real_guzzle_client_maps_a_refused_connection(): void
    {
        $this->expectException(ConnectionException::class);

        (new SendKuy('sk_test_123', ['base_url' => 'http://127.0.0.1:1/api/v1', 'max_retries' => 0, 'timeout' => 2]))->account()->get();
    }

    public function test_a_key_with_a_trailing_newline_is_trimmed(): void
    {
        $client = new FakeClient([FakeClient::json(200, ['data' => ['object' => 'account', 'id' => 'usr_1']])]);

        (new SendKuy("sk_test_123\n", ['http_client' => $client]))->account()->get();

        $this->assertSame('Bearer sk_test_123', $client->requests[0]->getHeaderLine('Authorization'));
    }

    public function test_a_body_that_cannot_be_encoded_is_a_sendkuy_exception(): void
    {
        $client = new FakeClient;

        try {
            $this->transport($client)->send('POST', '/messages', [], ['body' => "\xB1\x31"]);
            $this->fail('Expected a SendKuyException');
        } catch (SendKuyException $e) {
            $this->assertSame(SendKuyException::class, $e::class);
        }

        $this->assertSame([], $client->requests);
    }

    public function test_the_key_never_shows_up_in_a_dump(): void
    {
        $dump = print_r(new SendKuy('sk_live_secret_value_123', ['http_client' => new FakeClient]), true);

        $this->assertStringNotContainsString('sk_live_secret_value_123', $dump);
        $this->assertStringContainsString('[redacted]', $dump);
    }

    public function test_sends_an_empty_object_for_an_empty_body(): void
    {
        $client = new FakeClient([self::error(422, 'invalid_request_error', 'validation_failed')]);

        try {
            $this->transport($client)->send('POST', '/messages', [], []);
        } catch (InvalidRequestException) {
        }

        $this->assertSame('{}', (string) $client->requests[0]->getBody());
        $this->assertSame('application/json', $client->requests[0]->getHeaderLine('Content-Type'));
    }
}
