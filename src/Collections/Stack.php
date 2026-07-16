<?php

declare(strict_types=1);

namespace Atom\Collections;

/**
 * @template TValue
 * @extends ReadOnlyCollection<TValue>
 */
class Stack extends ReadOnlyCollection
{
    public function push(mixed $value): void
    {
        $this->items[] = $value;
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function peek(): mixed
    {
        return $this->last();
    }
}
