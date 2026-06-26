<?php

declare(strict_types=1);

namespace Atom\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class ReadOnlyCollection implements Countable, IteratorAggregate, JsonSerializable
{
    protected array $items = [];

    public function __construct(iterable $items)
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items);
    }

    public static function from(iterable $items): static
    {
        return new static($items);
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function containsAny(iterable $values): bool
    {
        foreach ($values as $value) {
            if ($this->contains($value)) {
                return true;
            }
        }

        return false;
    }

    public function containsAll(iterable $values): bool
    {
        foreach ($values as $value) {
            if (!$this->contains($value)) {
                return false;
            }
        }

        return true;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    public function hasAny(): bool
    {
        return count($this->items) > 0;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function filter(callable $predicate): static
    {
        $items = array_values(array_filter($this->items, $predicate));
        return new static($items);
    }

    public function map(callable $mapper): static
    {
        $items = array_map($mapper, $this->items);
        return new static($items);
    }

    public function flatMap(callable $mapper): static
    {
        $items = array_map($mapper, $this->items);
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                foreach ($item as $subItem) {
                    $result[] = $subItem;
                }
            } else {
                $result[] = $item;
            }
        }
        return new static($result);
    }

    public function reduce(callable $reducer, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $reducer, $initial);
    }

    public function reversed(): static
    {
        $items = array_reverse($this->items, false);
        return new static($items);
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function at(int $index, mixed $default = null): mixed
    {
        return $this->items[$index] ?? $default;
    }

    public function last(): mixed
    {
        if (count($this->items) > 0) {
            $end = end($this->items);
            reset($this->items);
            return $end;
        }
        return null;
    }

    public function concat(iterable $list): static
    {
        $items = is_array($list) ? $list : iterator_to_array($list);
        $items = array_merge($this->items, $items);
        return new static($items);
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    public function take(int $count): static
    {
        return $this->slice(0, $count);
    }

    public function skip(int $count): static
    {
        return $this->slice($count);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function values(): array
    {
        return array_values($this->items);
    }

    public function unique(): static
    {
        return new static(array_values(array_unique($this->items)));
    }

    public function implode(string $separator): string
    {
        return implode($separator, $this->items);
    }

    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function chunkBy(int $size): static
    {
        return new static(array_chunk($this->items, $size));
    }

    public function sorted(?callable $comparator = null): static
    {
        $items = $this->items;
        if ($comparator === null) {
            sort($items);
        } else {
            usort($items, $comparator);
        }
        return new static($items);
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $value) {
            $callback($value, $key, $this);
        }
        return $this;
    }
}
