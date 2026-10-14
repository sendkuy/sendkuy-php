<?php

namespace SendKuy\Resources;

use SendKuy\Objects\WorkflowExecution;

/**
 * @phpstan-import-type Options from Resource
 */
final class Executions extends Resource
{
    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): WorkflowExecution
    {
        return $this->item(WorkflowExecution::class, 'GET', self::path('/executions/%s', $id), options: $options);
    }
}
