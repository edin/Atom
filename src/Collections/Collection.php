<?php

namespace Atom\Collections;

class Collection extends ReadOnlyCollection implements ICollection
{
    public static function from(iterable $items): self
    {
        return new self($items);
    }

    public function add($value): void
    {
        $this->items[] = $value;
    }

    public function removeFirst($value): void
    {
        $key = array_search($value, $this->items, true);
        if ($key !== false) {
            unset($this->items[$key]);
        }
    }

    public function removeAll($value): void
    {
        $this->items = array_filter($this->items, function ($it) use ($value) {
            return $it !== $value;
        });
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function removeKey($key): void
    {
        unset($this->items[$key]);
    }

    public function remove(callable $predicate): void
    {
        $this->items = array_filter($this->items, $predicate);
    }

    public function include(iterable $source): void
    {
        $items = is_array($source) ? $source : iterator_to_array($source);
        $this->items = array_merge($this->items, $items);
    }

    public function exclude(iterable $source): void
    {
        $items = is_array($source) ? $source : iterator_to_array($source);

        $this->items = array_filter($this->items, function ($it) use ($items) {
            return array_search($it, $items, true) !== false;
        });
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    public function sort(?callable $comaparator = null): void
    {
        if ($comaparator === null) {
            sort($this->items);
        } else {
            usort($this->items, $comaparator);
        }
    }
}
