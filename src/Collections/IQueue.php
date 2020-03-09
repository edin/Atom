<?php

namespace Atom\Collections;

interface IQueue extends IReadOnlyCollection
{
    public function enqueue($value): void;
    public function dequeue();
    public function peek();
}
