<?php

namespace Atom\Collections\Interfaces;

interface ISet extends ICollection
{
    public function union(iterable $set): ISet;
    public function intersect(iterable $set): ISet;
    public function except(iterable $set): ISet;
}
