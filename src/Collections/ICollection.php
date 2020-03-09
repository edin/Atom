<?php

namespace Atom\Collections;

interface ICollection extends IReadOnlyCollection
{
    public function add($value): void;
    public function remove($value): void;
    public function clear(): void;
}

interface IStack extends IReadOnlyCollection
{
    public function push($value): void;
    public function pop();
    public function peek();
}
