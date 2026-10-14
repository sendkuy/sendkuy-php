<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string|null $workflow_id
 * @property-read string|null $contact_id
 * @property-read string|null $status
 * @property-read string|null $started_at
 * @property-read string|null $completed_at
 * @property-read string|null $error
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class WorkflowExecution extends SendKuyObject {}
