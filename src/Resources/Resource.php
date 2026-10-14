<?php

namespace SendKuy\Resources;

use SendKuy\Exceptions\SendKuyException;
use SendKuy\Page;
use SendKuy\SendKuyObject;
use SendKuy\Transport;
use stdClass;

/**
 * @phpstan-type Options array{idempotency_key?: string, timeout?: float|int}
 */
abstract class Resource
{
    public function __construct(protected readonly Transport $transport) {}

    /**
     * @template T of SendKuyObject
     *
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @param  Options  $options
     * @return T
     */
    protected function item(string $class, string $method, string $path, array $query = [], ?array $body = null, array $options = []): SendKuyObject
    {
        $data = $this->transport->send($method, $path, $query, $body, $options)['data'] ?? null;

        return $class::make($data instanceof stdClass || is_array($data) ? $data : []);
    }

    /**
     * @template T of SendKuyObject
     *
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @param  Options  $options
     * @return list<T>
     */
    protected function items(string $class, string $method, string $path, array $query = [], ?array $body = null, array $options = []): array
    {
        $data = $this->transport->send($method, $path, $query, $body, $options)['data'] ?? null;

        return array_values(array_map(static fn (mixed $row) => $class::make($row instanceof stdClass || is_array($row) ? $row : []), is_array($data) ? $data : []));
    }

    /**
     * @template T of SendKuyObject
     *
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $query
     * @param  Options  $options
     * @return Page<T>
     */
    protected function page(string $class, string $path, array $query = [], array $options = []): Page
    {
        $response = $this->transport->send('GET', $path, $query, null, $options);

        return Page::fromResponse($class, $response, fn (string $cursor): Page => $this->page($class, $path, [...$query, 'starting_after' => $cursor], $options));
    }

    protected static function path(string $template, string ...$ids): string
    {
        foreach ($ids as $id) {
            if (in_array($id, ['', '.', '..'], true)) {
                throw new SendKuyException(sprintf('"%s" is not a SendKuy id. Pass the id SendKuy returned, such as msg_….', $id));
            }
        }

        return sprintf($template, ...array_map(rawurlencode(...), $ids));
    }
}
