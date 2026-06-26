<?php

declare(strict_types=1);

namespace Atom\Collections;

class Set extends ReadOnlyCollection
{
    public function __construct(iterable $items)
    {
        parent::__construct($items);
        $this->items = array_values(array_unique($this->items));
    }

    public function add(mixed $value): void
    {
        if (!$this->contains($value)) {
            $this->items[] = $value;
        }
    }

    public function remove(mixed $value): void
    {
        $this->items = array_values(array_filter($this->items, function (mixed $it) use ($value): bool {
            return $it !== $value;
        }));
    }

    public function union(iterable $set): static
    {
        $items = $this->concat($set)->unique();
        return new static($items);
    }

    public function intersect(iterable $set): static
    {
        $collection = static::from($set);

        $items = $this->filter(function (mixed $it) use ($collection): bool {
            return $collection->contains($it);
        })->unique();

        return new static($items);
    }

    public function except(iterable $set): static
    {
        $collection = static::from($set);

        $items = $this->filter(function (mixed $it) use ($collection): bool {
            return !$collection->contains($it);
        })->unique();

        return new static($items);
    }
}
