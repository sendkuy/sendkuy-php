<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Job;

/**
 * @phpstan-import-type Options from Resource
 */
final class Jobs extends Resource
{
    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Job
    {
        return $this->item(Job::class, 'GET', self::path('/jobs/%s', $id), options: $options);
    }
}
