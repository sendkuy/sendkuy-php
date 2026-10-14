<?php

namespace SendKuy;

use Psr\Http\Client\ClientInterface;
use SendKuy\Exceptions\SendKuyException;
use SendKuy\Resources\Account;
use SendKuy\Resources\Campaigns;
use SendKuy\Resources\Contacts;
use SendKuy\Resources\Conversations;
use SendKuy\Resources\Executions;
use SendKuy\Resources\Gateways;
use SendKuy\Resources\Groups;
use SendKuy\Resources\Jobs;
use SendKuy\Resources\Messages;
use SendKuy\Resources\Numbers;
use SendKuy\Resources\Templates;
use SendKuy\Resources\Verifications;
use SendKuy\Resources\Webhooks;
use SendKuy\Resources\Workflows;
use stdClass;

final class SendKuy
{
    public const VERSION = '1.0.0';

    public const DEFAULT_BASE_URL = 'https://app.sendkuy.com/api/v1';

    private readonly Transport $transport;

    private readonly Account $account;

    private readonly Messages $messages;

    private readonly Numbers $numbers;

    private readonly Verifications $verifications;

    private readonly Jobs $jobs;

    private readonly Contacts $contacts;

    private readonly Groups $groups;

    private readonly Templates $templates;

    private readonly Gateways $gateways;

    private readonly Webhooks $webhooks;

    private readonly Campaigns $campaigns;

    private readonly Conversations $conversations;

    private readonly Workflows $workflows;

    private readonly Executions $executions;

    /**
     * @param  array{base_url?: string, timeout?: float|int, max_retries?: int, http_client?: ClientInterface, sleep?: callable(float): void}  $options
     */
    public function __construct(#[\SensitiveParameter] string $apiKey, array $options = [])
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new SendKuyException('Pass your SendKuy API key to new SendKuy(), for example the value of the SENDKUY_API_KEY environment variable.');
        }

        $this->transport = new Transport($apiKey, $options);
        $this->account = new Account($this->transport);
        $this->messages = new Messages($this->transport);
        $this->numbers = new Numbers($this->transport);
        $this->verifications = new Verifications($this->transport);
        $this->jobs = new Jobs($this->transport);
        $this->contacts = new Contacts($this->transport);
        $this->groups = new Groups($this->transport);
        $this->templates = new Templates($this->transport);
        $this->gateways = new Gateways($this->transport);
        $this->webhooks = new Webhooks($this->transport);
        $this->campaigns = new Campaigns($this->transport);
        $this->conversations = new Conversations($this->transport);
        $this->workflows = new Workflows($this->transport);
        $this->executions = new Executions($this->transport);
    }

    public function account(): Account
    {
        return $this->account;
    }

    public function messages(): Messages
    {
        return $this->messages;
    }

    public function numbers(): Numbers
    {
        return $this->numbers;
    }

    public function verifications(): Verifications
    {
        return $this->verifications;
    }

    public function jobs(): Jobs
    {
        return $this->jobs;
    }

    public function contacts(): Contacts
    {
        return $this->contacts;
    }

    public function groups(): Groups
    {
        return $this->groups;
    }

    public function templates(): Templates
    {
        return $this->templates;
    }

    public function gateways(): Gateways
    {
        return $this->gateways;
    }

    public function webhooks(): Webhooks
    {
        return $this->webhooks;
    }

    public function campaigns(): Campaigns
    {
        return $this->campaigns;
    }

    public function conversations(): Conversations
    {
        return $this->conversations;
    }

    public function workflows(): Workflows
    {
        return $this->workflows;
    }

    public function executions(): Executions
    {
        return $this->executions;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['transport' => $this->transport];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array{idempotency_key?: string, timeout?: float|int}  $options
     * @return array<array-key, mixed>
     */
    public function request(string $method, string $path, array $params = [], array $options = []): array
    {
        $response = strtoupper($method) === 'GET'
            ? $this->transport->send($method, $path, $params, null, $options)
            : $this->transport->send($method, $path, [], $params === [] ? null : $params, $options);

        $plain = self::plain($response);

        return is_array($plain) ? $plain : [];
    }

    private static function plain(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $value = get_object_vars($value);
        }

        return is_array($value) ? array_map(self::plain(...), $value) : $value;
    }
}
