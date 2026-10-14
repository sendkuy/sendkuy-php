<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $type
 * @property-read string $name
 * @property-read string $email
 * @property-read SendKuyObject|null $plan
 * @property-read SendKuyObject|null $credits
 * @property-read int $rate_limit
 */
final class Account extends SendKuyObject {}
