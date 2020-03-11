<?php

namespace Atom\Collections;

use ArrayAccess;

interface ICollection extends IReadOnlyCollection, ArrayAccess
{
    public function add($value): void;
    public function remove($value): void;
    public function clear(): void;
}
