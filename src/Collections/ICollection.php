<?php

namespace Atom\Collections;

use ArrayAccess;
use Closure;

interface ICollection extends IReadOnlyCollection //, ArrayAccess
{
    public function add($value): void;
    // public function removeFirstValue($value): void;
    // public function removeAllValues($value): void;
    // public function removeKey($key): void;
    // public function removeWhere(Closure $closure);
    public function clear(): void;
}
