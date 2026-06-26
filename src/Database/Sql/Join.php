<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

use InvalidArgumentException;

final class Join
{
    public const INNER = "INNER JOIN";
    public const LEFT = "LEFT JOIN";
    public const RIGHT = "RIGHT JOIN";

    private WhereGroup $on;

    private function __construct(public readonly string $type, public readonly Table $table)
    {
        $this->on = new WhereGroup();
    }

    public static function inner(string $table): self
    {
        return self::from(self::INNER, $table);
    }

    public static function left(string $table): self
    {
        return self::from(self::LEFT, $table);
    }

    public static function right(string $table): self
    {
        return self::from(self::RIGHT, $table);
    }

    private static function from(string $type, string $definition): self
    {
        [$table, $on] = self::parseDefinition($definition);

        $join = new self($type, Table::from($table));
        if ($on !== null) {
            [$left, $operator, $right] = self::parseOn($on);
            $join->on($left, Op::column($right, $operator));
        }

        return $join;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private static function parseDefinition(string $definition): array
    {
        $parts = preg_split('/\s+on\s+/i', trim($definition), 2);
        return [$parts[0], $parts[1] ?? null];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private static function parseOn(string $expression): array
    {
        if (!preg_match('/^\s*(\S+)\s*(=|<>|!=|>=|<=|>|<)\s*(\S+)\s*$/', $expression, $matches)) {
            throw new InvalidArgumentException("Unable to parse join ON expression '{$expression}'.");
        }

        return [$matches[1], $matches[2] === "!=" ? "<>" : $matches[2], $matches[3]];
    }

    public function on(string $column, mixed $value): self
    {
        $this->on->where($column, $value);
        return $this;
    }

    public function orOn(string $column, mixed $value): self
    {
        $this->on->orWhere($column, $value);
        return $this;
    }

    public function onGroup(callable $builder): self
    {
        $this->on->group($builder);
        return $this;
    }

    public function getOn(): WhereGroup
    {
        return $this->on;
    }
}
