<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $channel
 * @property-read string $name
 * @property-read string|null $subject
 * @property-read string|null $body
 * @property-read string|null $status
 * @property-read bool $read_only
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class Template extends SendKuyObject {}
