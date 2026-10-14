<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Account as AccountObject;
use SendKuy\Objects\AccountUsage;

/**
 * @phpstan-import-type Options from Resource
 */
final class Account extends Resource
{
    /**
     * @param  Options  $options
     */
    public function get(array $options = []): AccountObject
    {
        return $this->item(AccountObject::class, 'GET', '/account', options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function usage(array $options = []): AccountUsage
    {
        return $this->item(AccountUsage::class, 'GET', '/account/usage', options: $options);
    }
}
