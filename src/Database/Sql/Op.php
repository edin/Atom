<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final readonly class Op
{
    private function __construct(
        public string $operator,
        public mixed $value = null,
        public mixed $maxValue = null
    ) {
    }

    public static function from(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null) {
            return self::isNull();
        }

        if (is_array($value)) {
            return self::in($value);
        }

        return self::eq($value);
    }

    public static function eq(mixed $value): self
    {
        return new self("=", $value);
    }

    public static function ne(mixed $value): self
    {
        return new self("<>", $value);
    }

    public static function gt(mixed $value): self
    {
        return new self(">", $value);
    }

    public static function gte(mixed $value): self
    {
        return new self(">=", $value);
    }

    public static function lt(mixed $value): self
    {
        return new self("<", $value);
    }

    public static function lte(mixed $value): self
    {
        return new self("<=", $value);
    }

    /**
     * @param array<int, mixed> $values
     */
    public static function in(array $values): self
    {
        return new self("IN", $values);
    }

    public static function like(string $value): self
    {
        return new self("LIKE", $value);
    }

    public static function ilike(string $value): self
    {
        return new self("ILIKE", $value);
    }

    public static function between(mixed $min, mixed $max): self
    {
        return new self("BETWEEN", $min, $max);
    }

    public static function isNull(): self
    {
        return new self("IS NULL");
    }

    public static function isNotNull(): self
    {
        return new self("IS NOT NULL");
    }

    public static function column(string $column, string $operator = "="): self
    {
        return new self($operator, Column::from($column));
    }
}
