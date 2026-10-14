<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string $event
 * @property-read int $attempt
 * @property-read int|null $status_code
 * @property-read string|null $error
 * @property-read string|null $response_excerpt
 * @property-read string|null $delivered_at
 * @property-read string|null $next_retry_at
 * @property-read string $created_at
 */
final class WebhookDelivery extends SendKuyObject {}
