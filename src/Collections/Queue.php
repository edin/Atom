<?php

namespace Atom\Collections;

class Queue extends ReadOnlyCollection implements IQueue
{
    public static function from(iterable $items): self
    {
        return new self($items);
    }

    public function enqueue($value): void
    {
        $this->items[] = $value;
    }

    public function dequeue()
    {
        return array_shift($this->items);
    }

    public function peek()
    {
        return $this->first();
    }
}
