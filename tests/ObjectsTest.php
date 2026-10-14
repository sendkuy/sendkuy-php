<?php

namespace SendKuy\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SendKuy\Objects\Message;
use SendKuy\SendKuy;
use SendKuy\SendKuyObject;
use SendKuy\Tests\Support\FakeClient;
use SendKuy\Tools\ObjectGenerator;

final class ObjectsTest extends TestCase
{
    public function test_generated_objects_match_the_spec(): void
    {
        $expected = ObjectGenerator::fromFile(__DIR__.'/../spec/openapi.json')->files();
        $actual = [];

        foreach (glob(__DIR__.'/../src/Objects/*.php') ?: [] as $file) {
            $actual[basename($file)] = (string) file_get_contents($file);
        }

        ksort($expected);
        ksort($actual);

        $this->assertSame($expected, $actual, 'src/Objects is stale. Run: composer generate');
    }

    public function test_empty_json_objects_stay_objects(): void
    {
        $body = '{"data":{"object":"contact","id":"con_1","attributes":{},"tags":[]}}';
        $client = new FakeClient([new Response(200, ['Content-Type' => 'application/json'], $body)]);

        $contact = (new SendKuy('sk_test_123', ['http_client' => $client]))->contacts()->get('con_1');

        $this->assertInstanceOf(SendKuyObject::class, $contact->attributes);
        $this->assertNull($contact->attributes->company);
        $this->assertSame([], $contact->tags);
        $this->assertSame('{"object":"contact","id":"con_1","attributes":{},"tags":[]}', json_encode($contact));
    }

    public function test_nested_objects_take_their_class_from_the_object_field(): void
    {
        $object = SendKuyObject::make([
            'id' => 'cnv_1',
            'last_message' => ['object' => 'message', 'id' => 'msg_1'],
            'tags' => ['vip', 'reseller'],
            'unknown' => ['object' => 'not_a_type_yet', 'id' => 'x_1'],
        ]);

        $this->assertInstanceOf(Message::class, $object->last_message);
        $this->assertSame('msg_1', $object['last_message']['id']);
        $this->assertSame(['vip', 'reseller'], $object->tags);
        $this->assertSame(SendKuyObject::class, $object->unknown::class);
        $this->assertSame('x_1', $object->toArray()['unknown']['id']);
    }
}
