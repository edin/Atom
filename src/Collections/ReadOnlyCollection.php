<?php

namespace Atom\Collections;

use ArrayIterator;

class ReadOnlyCollection implements IReadOnlyCollection
{
    protected $items = [];

    public function __construct(iterable $items)
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items);
    }

    public static function from(iterable $items): self
    {
        return new self($items);
    }

    public function contains($value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return count($this->items) == 0;
    }

    public function hasAny(): bool
    {
        return count($this->items) > 0;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function filter(callable $predicate): self
    {
        $items = array_filter($this->items, $predicate);
        //$items = array_values($items);
        return new static($items);
    }

    public function map(callable $mapper): self
    {
        $items = array_map($mapper, $this->items);
        return new static($items);
    }

    public function flatMap(callable $mapper): self
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

    public function reduce(callable $reducer, $intial = null)
    {
        return array_reduce($this->items, $reducer, $intial);
    }

    public function reversed(): self
    {
        $items = array_reverse($this->items, false);
        return new static($items);
    }

    public function first()
    {
        return $this->items[0] ?? null;
    }

    public function last()
    {
        if (count($this->items) > 0) {
            $end = end($this->items);
            reset($this->items);
            return $end;
        }
        return null;
    }

    public function concat(iterable $list): self
    {
        $items = is_array($list) ? $list : iterator_to_array($list);
        $items = array_merge($this->items, $items);
        return new self($items);
    }

    public function getIterator()
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

    public function unique(): self
    {
        return new self(array_unique($this->items));
    }

    public function implode(string $saprator): string
    {
        return implode($saprator, $this->items);
    }

    public function jsonSerialize()
    {
        return $this->items;
    }

    public function chunkBy(int $size): self
    {
        return new self(array_chunk($this->items, $size));
    }

    public function sorted(?callable $comaparator = null): self
    {
        $items = $this->items;
        if ($comaparator === null) {
            sort($items);
        } else {
            usort($items, $comaparator);
        }
        return new self($items);
    }
}
