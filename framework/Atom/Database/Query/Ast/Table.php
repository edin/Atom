<?php

namespace Atom\Database\Query;

final class Table
{
    public $name;
    public $alias;

    public static function fromValue(string $value): self
    {
        $parts = \explode(" ", $value);
        $t = new static();
        $t->name = $parts[0] ?? null;
        $t->alias = $parts[1] ?? null;
        return $t;
    }
}
