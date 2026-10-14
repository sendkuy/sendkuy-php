<?php

namespace SendKuy\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SendKuy\Exceptions\SendKuyException;
use SendKuy\Exceptions\SignatureVerificationException;
use SendKuy\Webhook;

final class WebhookTest extends TestCase
{
    /**
     * @return iterable<string, array{array{name: string, secret: string, payload: string, header: string, now: int, tolerance: int, valid: bool, reason: string|null}}>
     */
    public static function vectors(): iterable
    {
        $vectors = json_decode((string) file_get_contents(__DIR__.'/../spec/webhook-vectors.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach ($vectors as $vector) {
            yield $vector['name'] => [$vector];
        }
    }

    public function test_an_empty_secret_is_refused_with_a_clear_message(): void
    {
        $this->expectException(SendKuyException::class);
        $this->expectExceptionMessage('whsec_');

        Webhook::verify('{}', 't=1,v1='.str_repeat('a', 64), '');
    }

    /**
     * @param  array{name: string, secret: string, payload: string, header: string, now: int, tolerance: int, valid: bool, reason: string|null}  $vector
     */
    #[DataProvider('vectors')]
    public function test_matches_the_server_on_every_vector(array $vector): void
    {
        try {
            $event = Webhook::verify($vector['payload'], $vector['header'], $vector['secret'], $vector['tolerance'], $vector['now']);
        } catch (SignatureVerificationException $e) {
            $this->assertSame($vector['reason'], $e->getReason());

            return;
        }

        $this->assertNull($vector['reason'], "Expected {$vector['reason']}");
        $this->assertSame(json_decode($vector['payload'], true), $event);
    }
}
