<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read int $workflows_triggered
 * @property-read int $contacts_enrolled
 * @property-read list<mixed> $errors
 */
final class WorkflowTrigger extends SendKuyObject {}
