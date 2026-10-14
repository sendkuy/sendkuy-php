<?php

namespace SendKuy\Tests;

use PHPUnit\Framework\TestCase;
use SendKuy\SendKuy;
use SendKuy\Tests\Support\FakeClient;

final class PageTest extends TestCase
{
    public function test_auto_paginate_follows_next_cursor_with_the_original_params(): void
    {
        $client = new FakeClient([
            FakeClient::json(200, ['data' => [['id' => 'con_1']], 'meta' => ['has_more' => true, 'next_cursor' => '1287']]),
            FakeClient::json(200, ['data' => [['id' => 'con_2']], 'meta' => ['has_more' => false, 'next_cursor' => null]]),
        ]);

        $ids = [];

        foreach ((new SendKuy('sk_test_123', ['http_client' => $client]))->contacts()->list(['limit' => 1])->autoPaginate() as $contact) {
            $ids[] = $contact->id;
        }

        $this->assertSame(['con_1', 'con_2'], $ids);
        $this->assertCount(2, $client->requests);
        parse_str($client->requests[1]->getUri()->getQuery(), $second);
        $this->assertSame(['limit' => '1', 'starting_after' => '1287'], $second);
    }
}
