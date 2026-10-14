<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Gateway;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Gateways extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Gateway>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Gateway::class, '/gateways', $params, $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Gateway
    {
        return $this->item(Gateway::class, 'GET', self::path('/gateways/%s', $id), options: $options);
    }
}
