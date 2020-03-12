<?php

namespace Atom\Collections;

class Stack extends Collection implements ISet
{
    public function union(iterable $set): ISet
    {
        return $this;
    }

    public function intersect(iterable $set): ISet
    {
        return $this;
    }

    public function except(iterable $set): ISet
    {
        return $this;
    }
}
