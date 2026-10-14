<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read string|null $conversation_id
 * @property-read string|null $body
 * @property-read string $created_at
 * @property-read string $updated_at
 */
final class ConversationMessage extends SendKuyObject {}
