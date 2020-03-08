<?php

namespace Atom\Collections;

class Collection implements ICollection
{
    private $items = [];

    public function add($value)
    {
        $this->items[] = $value;
    }

    public function remove($value)
    {
        //Remove element from array
    }

    public function clear()
    {
        $this->items = [];
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
