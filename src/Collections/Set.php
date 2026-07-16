<?php

declare(strict_types=1);

namespace Atom\Collections;

/**
 * @template TValue
 * @extends ReadOnlyCollection<TValue>
 */
class Set extends ReadOnlyCollection
{
    /** @param iterable<array-key, TValue> $items */
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

    /**
     * @param iterable<array-key, TValue> $set
     * @return static<TValue>
     */
    public function union(iterable $set): static
    {
        $items = $this->concat($set)->unique();
        return new static($items);
    }

    /**
     * @param iterable<array-key, mixed> $set
     * @return static<TValue>
     */
    public function intersect(iterable $set): static
    {
        $collection = static::from($set);

        $items = $this->filter(function (mixed $it) use ($collection): bool {
            return $collection->contains($it);
        })->unique();

        return new static($items);
    }

    /**
     * @param iterable<array-key, mixed> $set
     * @return static<TValue>
     */
    public function except(iterable $set): static
    {
        $collection = static::from($set);

        $items = $this->filter(function (mixed $it) use ($collection): bool {
            return !$collection->contains($it);
        })->unique();

        return new static($items);
    }
}
