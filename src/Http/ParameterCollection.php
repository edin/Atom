<?php

declare(strict_types=1);

namespace Atom\Http;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class ParameterCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private array $items = [])
    {
    }

    /**
     * @param array<string, mixed> $items
     */
    public static function from(array $items): self
    {
        return new self($items);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->items);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->items[$name] ?? $default;
    }

    public function string(string $name, string $default = ""): string
    {
        $value = $this->get($name, $default);
        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $name, int $default = 0): int
    {
        $value = $this->get($name);
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $default;
    }

    public function bool(string $name, bool $default = false): bool
    {
        $value = filter_var($this->get($name), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        return $value ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function only(array $names): array
    {
        return array_intersect_key($this->items, array_flip($names));
    }

    /**
     * @return array<string, mixed>
     */
    public function except(array $names): array
    {
        return array_diff_key($this->items, array_flip($names));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? $this->get($offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new \InvalidArgumentException("Parameter key must be a string.");
        }

        $this->items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            unset($this->items[$offset]);
        }
    }
}
