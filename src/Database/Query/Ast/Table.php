<?php

declare(strict_types=1);

namespace Atom\Database\Query\Ast;

final class Table extends Node
{
    public ?string $name = null;
    public ?string $alias = null;

    public static function fromValue(string $value): self
    {
        $parts = \explode(" ", $value);
        $t = new static();
        $t->name = $parts[0] ?? null;
        $t->alias = $parts[1] ?? null;
        return $t;
    }
}
