<?php

namespace Atom\Collections;

class Collection extends ReadOnlyCollection implements ICollection
{
    private $items = [];

    public function add($value): void
    {
        $this->items[] = $value;
    }

    public function remove($value): void
    {
        $key = array_search($value, $this->items, true);
        if ($key) {
            unset($this->items[$key]);
            $this->items = array_values($this->items);
        }
    }

    public function clear(): void
    {
        $this->items = [];
    }
}
