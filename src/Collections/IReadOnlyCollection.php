<?php

namespace Atom\Collections;

use Countable;

interface IReadOnlyCollection extends Countable
{
    public function contains($value): bool;
    public function count(): int;
    public function isEmpty(): bool;
    public function hasAny(): bool;
    public function toArray(): array;
}
