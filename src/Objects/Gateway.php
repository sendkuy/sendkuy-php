<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $channel
 * @property-read string $type
 * @property-read string $name
 * @property-read string|null $address
 * @property-read string $status
 * @property-read bool $is_default
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class Gateway extends SendKuyObject {}
