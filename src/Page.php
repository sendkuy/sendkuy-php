<?php

namespace SendKuy;

use ArrayIterator;
use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use stdClass;

/**
 * @template T of SendKuyObject
 *
 * @implements IteratorAggregate<int, T>
 */
final class Page implements Countable, IteratorAggregate
{
    /**
     * @param  list<T>  $data
     * @param  Closure(string): Page<T>  $next
     */
    public function __construct(
        public readonly array $data,
        public readonly bool $hasMore,
        public readonly ?string $nextCursor,
        private readonly Closure $next,
    ) {}

    /**
     * @template U of SendKuyObject
     *
     * @param  class-string<U>  $class
     * @param  array<array-key, mixed>  $response
     * @param  Closure(string): Page<U>  $next
     * @return Page<U>
     */
    public static function fromResponse(string $class, array $response, Closure $next): self
    {
        $rows = is_array($response['data'] ?? null) ? $response['data'] : [];
        $meta = $response['meta'] ?? null;
        $meta = $meta instanceof stdClass ? get_object_vars($meta) : (is_array($meta) ? $meta : []);
        $cursor = $meta['next_cursor'] ?? null;

        return new self(
            array_values(array_map(static fn (mixed $row) => $class::make($row instanceof stdClass || is_array($row) ? $row : []), $rows)),
            ($meta['has_more'] ?? false) === true,
            is_string($cursor) || is_int($cursor) ? (string) $cursor : null,
            $next,
        );
    }

    /**
     * @return Page<T>|null
     */
    public function nextPage(): ?self
    {
        if (! $this->hasMore || $this->nextCursor === null) {
            return null;
        }

        return ($this->next)($this->nextCursor);
    }

    /**
     * @return Generator<int, T>
     */
    public function autoPaginate(): Generator
    {
        $page = $this;

        while ($page !== null) {
            foreach ($page->data as $item) {
                yield $item;
            }

            $page = $page->nextPage();
        }
    }

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }
}
