<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string|null $channel
 * @property-read string|null $status
 * @property-read string|null $address
 * @property-read string|null $contact_id
 * @property-read int $unread_count
 * @property-read string|null $last_message_at
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class Conversation extends SendKuyObject {}
