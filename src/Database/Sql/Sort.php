<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class Sort
{
    public const ASC = "ASC";
    public const DESC = "DESC";

    private function __construct(public Column $column, public string $direction)
    {
    }

    public static function asc(string $column): self
    {
        return new self(Column::from($column), self::ASC);
    }

    public static function desc(string $column): self
    {
        return new self(Column::from($column), self::DESC);
    }
}
