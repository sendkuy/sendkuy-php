<?php

namespace SendKuy\Resources;

use SendKuy\Objects\BatchRow;
use SendKuy\Objects\Message;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Messages extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Message>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Message::class, '/messages', $params, $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return list<Message>
     */
    public function send(array $params, array $options = []): array
    {
        return $this->items(Message::class, 'POST', '/messages', body: $params, options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return list<BatchRow>
     */
    public function sendBatch(array $params, array $options = []): array
    {
        return $this->items(BatchRow::class, 'POST', '/messages/batch', body: $params, options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Message
    {
        return $this->item(Message::class, 'GET', self::path('/messages/%s', $id), options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function cancel(string $id, array $options = []): Message
    {
        return $this->item(Message::class, 'POST', self::path('/messages/%s/cancel', $id), options: $options);
    }

    /**
     * @param  Options  $options
     */
    public function resend(string $id, array $options = []): Message
    {
        return $this->item(Message::class, 'POST', self::path('/messages/%s/resend', $id), options: $options);
    }
}
