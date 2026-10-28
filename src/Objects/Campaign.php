<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string $status
 * @property-read string $type
 * @property-read list<string> $channels
 * @property-read string|null $group_id
 * @property-read int $total_contacts
 * @property-read int $processed_contacts
 * @property-read string|null $scheduled_at
 * @property-read string|null $started_at
 * @property-read string|null $completed_at
 * @property-read SendKuyObject|null $recurrence
 * @property-read string|null $next_run_at
 * @property-read int|null $current_run
 * @property-read string|null $paused_reason
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read SendKuyObject|null $stats
 */
final class Campaign extends SendKuyObject {}
