<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Campaign;
use SendKuy\Objects\CampaignDispatch;
use SendKuy\Objects\Deleted;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Campaigns extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Campaign>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Campaign::class, '/campaigns', $params, $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function create(array $params, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'POST', '/campaigns', body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'GET', self::path('/campaigns/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function update(string $id, array $params, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'PATCH', self::path('/campaigns/%s', $id), body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function delete(string $id, array $options = []): Deleted
    {
        return $this->item(Deleted::class, 'DELETE', self::path('/campaigns/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<CampaignDispatch>
     */
    public function listDispatches(string $id, array $params = [], array $options = []): Page
    {
        return $this->page(CampaignDispatch::class, self::path('/campaigns/%s/dispatches', $id), $params, $options);
    }

    /**
     * @param  Options  $options
     */
    public function start(string $id, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'POST', self::path('/campaigns/%s/start', $id), options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function pause(string $id, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'POST', self::path('/campaigns/%s/pause', $id), options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function resume(string $id, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'POST', self::path('/campaigns/%s/resume', $id), options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function cancel(string $id, array $options = []): Campaign
    {
        return $this->item(Campaign::class, 'POST', self::path('/campaigns/%s/cancel', $id), options: $options);
    }
}
