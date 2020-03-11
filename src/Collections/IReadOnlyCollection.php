<?php

namespace Atom\Collections;

use Countable;
use Iterator;

interface IReadOnlyCollection extends Countable, Iterator
{
    public function contains($value): bool;
    public function count(): int;
    public function isEmpty(): bool;
    public function hasAny(): bool;
    public function toArray(): array;
    public function filter(callable $predicate): self;
    public function map(callable $mapper): self;
    public function flatMap(callable $mapper): self;
    public function reduce(callable $reducer);
    public function reverse(): self;
    public function first();
    public function last();
    public function concat(iterable $list): IReadOnlyCollection;
}
