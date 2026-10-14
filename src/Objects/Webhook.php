<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $url
 * @property-read list<string> $events
 * @property-read string $status
 * @property-read string $secret_preview
 * @property-read int $consecutive_failures
 * @property-read string|null $last_failure_at
 * @property-read string|null $disabled_at
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read string|null $secret
 */
final class Webhook extends SendKuyObject {}
