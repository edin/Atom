<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class Condition
{
    private function __construct(
        public string $boolean,
        public Column $column,
        public Op $op
    ) {
    }

    public static function and(string $column, mixed $value): self
    {
        return new self("AND", Column::from($column), Op::from($value));
    }

    public static function or(string $column, mixed $value): self
    {
        return new self("OR", Column::from($column), Op::from($value));
    }
}
