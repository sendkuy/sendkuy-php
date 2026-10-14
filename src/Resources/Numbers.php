<?php

namespace SendKuy\Resources;

use SendKuy\Objects\WhatsappNumberCheck;

/**
 * @phpstan-import-type Options from Resource
 */
final class Numbers extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return list<WhatsappNumberCheck>
     */
    public function check(array $params, array $options = []): array
    {
        return $this->items(WhatsappNumberCheck::class, 'POST', '/whatsapp/numbers/check', body: $params, options: $options);
    }
}
