<?php

declare(strict_types=1);

namespace Atom\Collections;

use ArrayAccess;

/**
 * @template TValue
 * @extends ReadOnlyCollection<TValue>
 * @implements ArrayAccess<array-key, TValue>
 */
class Collection extends ReadOnlyCollection implements ArrayAccess
{
    public function add(mixed $value): void
    {
        $this->items[] = $value;
    }

    public function removeFirst(mixed $value): void
    {
        $key = array_search($value, $this->items, true);
        if ($key !== false) {
            unset($this->items[$key]);
            $this->items = array_values($this->items);
        }
    }

    public function removeAll(mixed $value): void
    {
        $this->items = array_values(array_filter($this->items, function (mixed $it) use ($value): bool {
            return $it !== $value;
        }));
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function removeKey(mixed $key): void
    {
        unset($this->items[$key]);
        $this->items = array_values($this->items);
    }

    public function remove(callable $predicate): void
    {
        $this->items = array_values(array_filter($this->items, $predicate));
    }

    /** @param iterable<array-key, TValue> $source */
    public function include(iterable $source): void
    {
        $items = is_array($source) ? $source : iterator_to_array($source);
        $this->items = array_merge($this->items, $items);
    }

    /** @param iterable<array-key, mixed> $source */
    public function exclude(iterable $source): void
    {
        $items = is_array($source) ? $source : iterator_to_array($source);

        $this->items = array_values(array_filter($this->items, function (mixed $it) use ($items): bool {
            return array_search($it, $items, true) === false;
        }));
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function sort(?callable $comparator = null): void
    {
        if ($comparator === null) {
            sort($this->items);
        } else {
            usort($this->items, $comparator);
        }
    }
}
