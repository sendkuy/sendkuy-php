<?php

namespace SendKuy\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SendKuy\Exceptions\SendKuyException;
use SendKuy\Objects\ObjectTypes;
use SendKuy\Page;
use SendKuy\SendKuy;
use SendKuy\SendKuyObject;
use SendKuy\Tests\Support\FakeClient;

final class ContractTest extends TestCase
{
    private const PROBE = ['probe' => 'value'];

    private const REPLIES = [
        'ItemEnvelope' => ['data' => ['id' => 'x_1']],
        'ListEnvelope' => ['data' => [['id' => 'x_1']]],
        'CollectionEnvelope' => ['data' => [['id' => 'x_1']], 'meta' => ['has_more' => false, 'next_cursor' => null]],
    ];

    /** @var array<string, mixed>|null */
    private static ?array $spec = null;

    /**
     * @return array<string, mixed>
     */
    private static function spec(): array
    {
        return self::$spec ??= json_decode((string) file_get_contents(__DIR__.'/../spec/openapi.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    private static function operations(): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/../spec/operations.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return iterable<string, array{string, string, string, array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        foreach (self::spec()['paths'] as $path => $item) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $verb) {
                if (isset($item[$verb])) {
                    yield $item[$verb]['operationId'] => [$item[$verb]['operationId'], strtoupper($verb), $path, $item[$verb]];
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    #[DataProvider('cases')]
    public function test_every_operation_reaches_its_route_and_returns_its_envelope(string $operationId, string $verb, string $path, array $operation): void
    {
        $target = self::operations()[$operationId] ?? null;
        $this->assertIsString($target, "{$operationId} has no entry in spec/operations.json");
        [$group, $method] = explode('.', $target);
        [$envelope, $schema] = self::success($operation);

        $client = new FakeClient([FakeClient::json(200, self::REPLIES[$envelope])]);
        $sendkuy = new SendKuy('sk_test_123', ['http_client' => $client]);
        $hasBody = isset($operation['requestBody']);
        $hasQuery = self::hasQuery($operation);
        $args = array_fill(0, substr_count($path, '{'), 'obj_123');

        if ($hasBody || $hasQuery) {
            $args[] = self::PROBE;
        }

        $result = $sendkuy->{$group}()->{$method}(...$args);

        $request = $client->requests[0];
        $this->assertSame($verb, $request->getMethod());
        $this->assertSame('/api/v1'.str_replace('{id}', 'obj_123', $path), $request->getUri()->getPath());

        if ($hasBody) {
            $this->assertSame(self::PROBE, $client->body());
        } else {
            $this->assertSame('', (string) $request->getBody());
        }

        if ($hasQuery) {
            parse_str($request->getUri()->getQuery(), $query);
            $this->assertSame(self::PROBE, $query);
        }

        $item = match ($envelope) {
            'CollectionEnvelope' => $this->assertPage($result),
            'ListEnvelope' => $this->assertListOfOne($result),
            default => $result,
        };

        $this->assertInstanceOf(SendKuyObject::class, $item);
        $this->assertSame(self::classFor($schema), $item::class);
    }

    public function test_every_resource_method_is_in_the_map_and_nothing_else(): void
    {
        $sendkuy = new SendKuy('sk_test_123', ['http_client' => new FakeClient]);
        $found = [];

        foreach ((new ReflectionClass(SendKuy::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $accessor) {
            if ($accessor->isConstructor() || $accessor->getName() === 'request' || str_starts_with($accessor->getName(), '__')) {
                continue;
            }

            $resource = $accessor->invoke($sendkuy);

            foreach ((new ReflectionClass($resource))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() === $resource::class) {
                    $found[] = $accessor->getName().'.'.$method->getName();
                }
            }
        }

        $this->assertEqualsCanonicalizing(array_values(self::operations()), $found);
        $this->assertEqualsCanonicalizing(array_keys(self::operations()), array_keys(iterator_to_array(self::cases())));
    }

    public function test_ids_are_encoded_into_a_single_path_segment(): void
    {
        $client = new FakeClient([FakeClient::json(200, ['data' => ['id' => 'x_1']])]);

        (new SendKuy('sk_test_123', ['http_client' => $client]))->messages()->get("msg_1/../account\n");

        $this->assertSame('/api/v1/messages/msg_1%2F..%2Faccount%0A', $client->requests[0]->getUri()->getPath());
    }

    public function test_empty_and_dot_ids_are_refused_before_any_request(): void
    {
        $client = new FakeClient;
        $sendkuy = new SendKuy('sk_test_123', ['http_client' => $client]);

        foreach (['', '.', '..'] as $id) {
            try {
                $sendkuy->groups()->addContacts($id, ['contacts' => ['con_1']]);
                $this->fail("Expected '{$id}' to be refused");
            } catch (SendKuyException $e) {
                $this->assertStringContainsString('is not a SendKuy id', $e->getMessage());
            }
        }

        $this->assertSame([], $client->requests);
    }

    private function assertPage(mixed $result): mixed
    {
        $this->assertInstanceOf(Page::class, $result);
        $this->assertCount(1, $result->data);

        return $result->data[0];
    }

    private function assertListOfOne(mixed $result): mixed
    {
        $this->assertIsArray($result);
        $this->assertTrue(array_is_list($result));
        $this->assertCount(1, $result);

        return $result[0];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{string, string}
     */
    private static function success(array $operation): array
    {
        foreach ($operation['responses'] as $status => $response) {
            if (! str_starts_with((string) $status, '2')) {
                continue;
            }

            $envelope = null;
            $schema = null;

            foreach ($response['content']['application/json']['schema']['allOf'] as $part) {
                if (isset($part['$ref'])) {
                    $envelope = self::refName($part['$ref']);
                }

                if (isset($part['properties']['data'])) {
                    $data = $part['properties']['data'];
                    $schema = self::refName($data['$ref'] ?? $data['items']['$ref']);
                }
            }

            if ($envelope !== null && $schema !== null) {
                return [$envelope, $schema];
            }
        }

        self::fail("{$operation['operationId']} has no 2xx response shaped allOf[envelope, data]");
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private static function hasQuery(array $operation): bool
    {
        foreach ($operation['parameters'] ?? [] as $parameter) {
            if (isset($parameter['$ref'])) {
                $parameter = self::spec()['components']['parameters'][self::refName($parameter['$ref'])];
            }

            if (($parameter['in'] ?? null) === 'query') {
                return true;
            }
        }

        return false;
    }

    private static function classFor(string $schema): string
    {
        $const = self::objectConst(self::spec()['components']['schemas'][$schema]);

        return $const !== null ? ObjectTypes::MAP[$const] : 'SendKuy\\Objects\\'.$schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private static function objectConst(array $schema): ?string
    {
        if (isset($schema['$ref'])) {
            return self::objectConst(self::spec()['components']['schemas'][self::refName($schema['$ref'])]);
        }

        $const = $schema['properties']['object']['const'] ?? null;

        if (is_string($const)) {
            return $const;
        }

        foreach ($schema['allOf'] ?? [] as $part) {
            $found = self::objectConst($part);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private static function refName(string $ref): string
    {
        return substr($ref, strrpos($ref, '/') + 1);
    }
}
