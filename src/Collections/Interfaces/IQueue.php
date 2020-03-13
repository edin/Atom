<?php

namespace Atom\Collections\Interfaces;

interface IQueue extends IReadOnlyCollection
{
    public function enqueue($value): void;
    public function dequeue();
    public function peek();
}
