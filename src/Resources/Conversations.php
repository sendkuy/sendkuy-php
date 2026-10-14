<?php

namespace SendKuy\Resources;

use SendKuy\Objects\Conversation;
use SendKuy\Objects\ConversationMessage;
use SendKuy\Page;

/**
 * @phpstan-import-type Options from Resource
 */
final class Conversations extends Resource
{
    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<Conversation>
     */
    public function list(array $params = [], array $options = []): Page
    {
        return $this->page(Conversation::class, '/conversations', $params, $options);
    }

    /**
     * @param  Options  $options
     */
    public function get(string $id, array $options = []): Conversation
    {
        return $this->item(Conversation::class, 'GET', self::path('/conversations/%s', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     * @return Page<ConversationMessage>
     */
    public function listMessages(string $id, array $params = [], array $options = []): Page
    {
        return $this->page(ConversationMessage::class, self::path('/conversations/%s/messages', $id), $params, $options);
    }

    /**
     * @param  Options  $options
     */
    public function markRead(string $id, array $options = []): Conversation
    {
        return $this->item(Conversation::class, 'POST', self::path('/conversations/%s/read', $id), options: $options);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  Options  $options
     */
    public function reply(string $id, array $params, array $options = []): ConversationMessage
    {
        return $this->item(ConversationMessage::class, 'POST', self::path('/conversations/%s/reply', $id), body: $params, options: $options);
    }
}
