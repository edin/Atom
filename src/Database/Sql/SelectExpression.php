<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class SelectExpression
{
    private function __construct(public string $expression, public ?string $alias = null)
    {
    }

    public static function count(string $column = "*", ?string $alias = null): self
    {
        return new self("COUNT({$column})", $alias);
    }
}
