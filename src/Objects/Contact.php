<?php

namespace SendKuy\Objects;

use SendKuy\SendKuyObject;

/**
 * @property-read string $object
 * @property-read string $id
 * @property-read list<string>|null $group_ids
 * @property-read string|null $external_id
 * @property-read string|null $first_name
 * @property-read string|null $last_name
 * @property-read string|null $whatsapp
 * @property-read string|null $email
 * @property-read string|null $sms
 * @property-read SendKuyObject|null $attributes
 * @property-read string|null $status
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 * @property-read bool|null $deleted
 */
final class Contact extends SendKuyObject {}
