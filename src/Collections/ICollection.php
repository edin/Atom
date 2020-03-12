<?php

namespace Atom\Collections;

use ArrayAccess;
use Closure;

interface ICollection extends IReadOnlyCollection //, ArrayAccess
{
    public function add($value): void;
    // public function removeFirst($value): void;
    // public function removeAll($value): void;
    // public function removeKey($key): void;
    // public function remove(callable $predicate);
    // public function include(iterable $source);
    // public function exclude(iterable $source);
    // public
    public function clear(): void;
}
