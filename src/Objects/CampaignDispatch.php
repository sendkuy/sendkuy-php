<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string|null $campaign_id
 * @property-read string|null $contact_id
 * @property-read string|null $message_id
 * @property-read int $run
 * @property-read string $channel
 * @property-read string $status
 * @property-read string|null $scheduled_at
 * @property-read string|null $sent_at
 * @property-read string|null $delivered_at
 * @property-read string|null $error
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class CampaignDispatch extends SendKuyObject {}
