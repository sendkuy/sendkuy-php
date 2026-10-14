<?php

namespace SendKuy;

use ArrayAccess;
use JsonSerializable;
use LogicException;
use SendKuy\Objects\ObjectTypes;
use stdClass;

/**
 * @implements ArrayAccess<array-key, mixed>
 */
class SendKuyObject implements ArrayAccess, JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $attributes
     */
    final public function __construct(private readonly array $attributes = []) {}

    /**
     * @param  array<array-key, mixed>|stdClass  $attributes
     */
    public static function make(array|stdClass $attributes): static
    {
        $attributes = $attributes instanceof stdClass ? get_object_vars($attributes) : $attributes;

        return new static(array_map(self::hydrate(...), $attributes));
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     */
    public static function from(array $attributes): self
    {
        $type = $attributes['object'] ?? null;
        $class = is_string($type) ? (ObjectTypes::MAP[$type] ?? self::class) : self::class;

        return $class::make($attributes);
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function __set(string $name, mixed $value): void
    {
        throw new LogicException(static::class.' is read-only.');
    }

    public function __unset(string $name): void
    {
        throw new LogicException(static::class.' is read-only.');
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException(static::class.' is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException(static::class.' is read-only.');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return array_map(self::flatten(...), $this->attributes);
    }

    public function jsonSerialize(): object
    {
        return (object) $this->attributes;
    }

    private static function hydrate(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            return self::from(get_object_vars($value));
        }

        if (! is_array($value)) {
            return $value;
        }

        return array_is_list($value) ? array_map(self::hydrate(...), $value) : self::from($value);
    }

    private static function flatten(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        return is_array($value) ? array_map(self::flatten(...), $value) : $value;
    }
}
