<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $type
 * @property-read string $status
 * @property-read int $total
 * @property-read int $processed
 * @property-read int $failed
 * @property-read list<SendKuyObject> $errors
 * @property-read SendKuyObject|null $counts
 * @property-read string $created_at
 * @property-read string|null $completed_at
 */
final class Job extends SendKuyObject {}
