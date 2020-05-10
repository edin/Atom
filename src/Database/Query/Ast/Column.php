<?php

namespace Atom\Database\Query\Ast;

final class Column extends Node
{
    public $table = null;
    public $name = null;
    public $alias = null;
    public $expression = null;

    /**
     * Parses table reference, field name and alias from string like "table.field alias" or "field alias" or "field"
     */
    public static function fromValue(string $value): self
    {
        $parts = \explode(" ", $value);
        $t = new static();
        $t->name = $parts[0] ?? null;
        $t->alias = $parts[1] ?? null;

        $parts = \explode(".", $t->name);
        if (count($parts) > 1) {
            $t->table = $parts[0];
            $t->name  = $parts[1];
        }
        return $t;
    }

    public static function fromAlias(string $alias, $expression): self
    {
        $t = new static();
        $t->alias = $alias;
        $t->expression = $expression;
        return $t;
    }
}
