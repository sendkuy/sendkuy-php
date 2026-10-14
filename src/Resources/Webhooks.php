<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Deleted;
use SendKuy\Objects\Webhook;
use SendKuy\Objects\WebhookDelivery;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Webhooks extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Webhook>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Webhook::class, '/webhooks', $params, $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function create(array $params, array $options = []): Webhook
    {
        return $this->item(Webhook::class, 'POST', '/webhooks', body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Webhook
    {
        return $this->item(Webhook::class, 'GET', self::path('/webhooks/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function update(string $id, array $params, array $options = []): Webhook
    {
        return $this->item(Webhook::class, 'PATCH', self::path('/webhooks/%s', $id), body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function delete(string $id, array $options = []): Deleted
    {
        return $this->item(Deleted::class, 'DELETE', self::path('/webhooks/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<WebhookDelivery>
     */
    public function listDeliveries(string $id, array $params = [], array $options = []): Page
    {
        return $this->page(WebhookDelivery::class, self::path('/webhooks/%s/deliveries', $id), $params, $options);
    }
}
