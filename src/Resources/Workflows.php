<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Workflow;
use SendKuy\Objects\WorkflowExecution;
use SendKuy\Objects\WorkflowTrigger;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Workflows extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Workflow>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Workflow::class, '/workflows', $params, $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Workflow
    {
        return $this->item(Workflow::class, 'GET', self::path('/workflows/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<WorkflowExecution>
     */
    public function listExecutions(string $id, array $params = [], array $options = []): Page
    {
        return $this->page(WorkflowExecution::class, self::path('/workflows/%s/executions', $id), $params, $options);
    }

    /**
     * @param  Options  $options
     */
    public function trigger(string $id, array $options = []): WorkflowTrigger
    {
        return $this->item(WorkflowTrigger::class, 'POST', self::path('/workflows/%s/trigger', $id), options: $options);
    }
}
