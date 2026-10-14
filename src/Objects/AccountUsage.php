<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $type
 * @property-read SendKuyObject|null $period
 * @property-read SendKuyObject|null $usage
 */
final class AccountUsage extends SendKuyObject {}
