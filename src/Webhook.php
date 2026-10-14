<?php

namespace SendKuy;

use SendKuy\Exceptions\SendKuyException;
use SendKuy\Exceptions\SignatureVerificationException;
use stdClass;

final class Webhook
{
    public const TOLERANCE = 300;

    /**
     * @return array<array-key, mixed>
     *
     * @throws SignatureVerificationException
     * @throws SendKuyException
     */
    public static function verify(string $payload, string $signature, #[\SensitiveParameter] string $secret, int $tolerance = self::TOLERANCE, ?int $now = null): array
    {
        if ($secret === '') {
            throw new SendKuyException('Pass the webhook endpoint secret, whsec_…, to Webhook::verify().');
        }

        if (preg_match('/^t=(\d+),v1=([a-f0-9]{64})$/', trim($signature), $matches) !== 1) {
            throw new SignatureVerificationException(
                'malformed_header',
                'The X-SendKuy-Signature header is missing or not shaped t={timestamp},v1={signature}.',
            );
        }

        $timestamp = (int) $matches[1];

        if (abs(($now ?? time()) - $timestamp) > $tolerance) {
            throw new SignatureVerificationException(
                'timestamp_out_of_tolerance',
                "The signature is more than {$tolerance} seconds from now. Check the server clock, or this delivery is a replay.",
            );
        }

        if (! hash_equals(hash_hmac('sha256', $timestamp.'.'.$payload, $secret), $matches[2])) {
            throw new SignatureVerificationException(
                'signature_mismatch',
                'The signature does not match. Use the endpoint secret in full, including whsec_, and the raw request body before any JSON parsing.',
            );
        }

        if (! json_decode($payload) instanceof stdClass) {
            throw new SignatureVerificationException('invalid_payload', 'The request body is not a JSON object.');
        }

        $event = json_decode($payload, true);

        return is_array($event) ? $event : [];
    }
}
