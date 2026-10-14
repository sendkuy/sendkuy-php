<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $status
 * @property-read string|null $trigger_type
 * @property-read int $total_enrolled
 * @property-read int $total_completed
 * @property-read int $total_failed
 * @property-read string|null $last_triggered_at
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class Workflow extends SendKuyObject {}
