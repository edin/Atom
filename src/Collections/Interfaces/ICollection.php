<?php

namespace Atom\Collections\Interfaces;

use ArrayAccess;

interface ICollection extends IReadOnlyCollection, ArrayAccess
{
    public function add($value): void;
    public function removeFirst($value): void;
    public function removeAll($value): void;
    public function removeKey($key): void;
    public function remove(callable $predicate): void;
    public function include(iterable $source): void;
    public function exclude(iterable $source): void;
    public function clear(): void;
    public function sort(?callable $comaparator = null): void;
}
