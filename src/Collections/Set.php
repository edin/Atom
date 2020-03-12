<?php

namespace Atom\Collections;

class Set extends Collection implements ISet
{
    public static function from(iterable $items): self
    {
        return new self($items);
    }

    public function union(iterable $set): ISet
    {
        $items = $this->concat($set)->unique();
        return new Set($items);
    }

    public function intersect(iterable $set): ISet
    {
        $collection = Set::from($set);

        $items = $this->filter(function ($it) use ($collection) {
            return $collection->contains($it);
        })->unique();

        return new Set($items);
    }

    public function except(iterable $set): ISet
    {
        $collection = Set::from($set);

        $items = $this->filter(function ($it) use ($collection) {
            return !$collection->contains($it);
        })->unique();

        return new Set($items);
    }
}
