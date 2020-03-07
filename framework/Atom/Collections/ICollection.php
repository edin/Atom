<?php

namespace Atom\Collections;

use Countable;

interface IReadOnlyCollection extends Countable
{
    public function contains($value): bool;
    public function count(): int;
    public function isEmpty(): bool;
    public function hasAny(): bool;
    public function toArray(): array;
}

interface ICollection extends IReadOnlyCollection
{
    public function add($value): void;
    public function remove($value): void;
    public function clear($value): void;
}

interface IQueue extends ICollection
{
    public function enqueue($value): void;
    public function dequeue();
    public function peek();
}

interface ISet extends ICollection
{
    public function union(iterable $set);
    public function intersect(iterable $set);
    public function except(iterable $set);
}

interface IStack extends ICollection
{
    public function push($value);
    public function pop();
    public function peek();
}

class Collection implements ICollection
{
    private $items = [];

    public function add($value)
    {
        $this->items[] = $value;
    }

    public function remove($value)
    {
    }

    public function clear()
    {
        $this->items = [];
    }
}
