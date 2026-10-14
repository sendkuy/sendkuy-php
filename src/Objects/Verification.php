<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string|null $to
 * @property-read string|null $email
 * @property-read list<string> $channels
 * @property-read string|null $channel
 * @property-read string $status
 * @property-read list<SendKuyObject> $attempts
 * @property-read int $checks_remaining
 * @property-read string $expires_at
 * @property-read string|null $approved_at
 * @property-read string $created_at
 */
final class Verification extends SendKuyObject {}
