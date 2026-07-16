<?php

declare(strict_types=1);

namespace Atom\Collections;

/**
 * @template TValue
 * @extends ReadOnlyCollection<TValue>
 */
class Queue extends ReadOnlyCollection
{
    public function enqueue(mixed $value): void
    {
        $this->items[] = $value;
    }

    public function dequeue(): mixed
    {
        return array_shift($this->items);
    }

    public function peek(): mixed
    {
        return $this->first();
    }
}
