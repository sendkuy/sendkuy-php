<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Verification;

/**
 * @phpstan-import-type Options from Resource
 */
final class Verifications extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function create(array $params, array $options = []): Verification
    {
        return $this->item(Verification::class, 'POST', '/verifications', body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Verification
    {
        return $this->item(Verification::class, 'GET', self::path('/verifications/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function check(string $id, array $params, array $options = []): Verification
    {
        return $this->item(Verification::class, 'POST', self::path('/verifications/%s/check', $id), body: $params, options: $options);
    }
}
