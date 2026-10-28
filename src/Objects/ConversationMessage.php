<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string|null $id
 * @property-read string|null $conversation_id
 * @property-read string|null $body
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read string|null $channel
 * @property-read string|null $from
 * @property-read string|null $gateway_id
 * @property-read string|null $provider_message_id
 * @property-read string|null $received_at
 * @property-read string|null $subject
 * @property-read SendKuyObject|null $media
 */
final class ConversationMessage extends SendKuyObject {}
