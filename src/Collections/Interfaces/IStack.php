<?php

namespace Atom\Collections\Interfaces;

interface IStack extends IReadOnlyCollection
{
    public function push($value): void;
    public function pop();
    public function peek();
}
