<?php

namespace Atom\Collections;

use ArrayIterator;
use Atom\Collections\Interfaces\IReadOnlyCollection;

class ReadOnlyCollection implements IReadOnlyCollection
{
    protected $items = [];

    public function __construct(iterable $items)
    {
        $this->items = is_array($items) ? $items : iterator_to_array($items);
    }

    /**
     * @return self
     */
    public static function from(iterable $items)
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

    public function filter(callable $predicate): IReadOnlyCollection
    {
        $items = array_filter($this->items, $predicate);
        //$items = array_values($items);
        return new self($items);
    }

    public function map(callable $mapper): IReadOnlyCollection
    {
        $items = array_map($mapper, $this->items);
        return new self($items);
    }

    public function flatMap(callable $mapper): IReadOnlyCollection
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
        return new self($result);
    }

    public function reduce(callable $reducer, $intial = null)
    {
        return array_reduce($this->items, $reducer, $intial);
    }

    public function reversed(): IReadOnlyCollection
    {
        $items = array_reverse($this->items, false);
        return new self($items);
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

    public function concat(iterable $list): IReadOnlyCollection
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

    public function unique(): IReadOnlyCollection
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

    public function chunkBy(int $size): IReadOnlyCollection
    {
        return new self(array_chunk($this->items, $size));
    }

    public function sorted(?callable $comaparator = null): IReadOnlyCollection
    {
        $items = $this->items;
        if ($comaparator === null) {
            sort($items);
        } else {
            usort($items, $comaparator);
        }
        return new self($items);
    }

    public function each(callable $callback): IReadOnlyCollection {
        foreach($this->items as $key => $value) {
            $callback($value, $key, $this);
        }
        return $this;
    }
}
