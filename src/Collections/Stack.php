<?php

declare(strict_types=1);

namespace Atom\Collections;

use Atom\Collections\Interfaces\IStack;

class Stack extends ReadOnlyCollection implements IStack
{
    public static function from(iterable $items): self
    {
        return new self($items);
    }

    public function push($value): void
    {
        $this->items[] = $value;
    }

    public function pop()
    {
        return array_pop($this->items);
    }

    public function peek()
    {
        return $this->last();
    }
}
