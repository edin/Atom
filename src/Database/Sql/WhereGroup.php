<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final class WhereGroup
{
    /** @var array<int, Condition|CriteriaExpression|self> */
    private array $items = [];

    public function __construct(public readonly string $boolean = "AND")
    {
    }

    public function where(string $column, mixed $value): self
    {
        $this->items[] = Condition::and($column, $value);
        return $this;
    }

    public function orWhere(string $column, mixed $value): self
    {
        $this->items[] = Condition::or($column, $value);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function whereExp(string $expression, array $parameters = []): self
    {
        $this->items[] = CriteriaExpression::and($expression, $parameters);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function orWhereExp(string $expression, array $parameters = []): self
    {
        $this->items[] = CriteriaExpression::or($expression, $parameters);
        return $this;
    }

    public function group(callable $builder): self
    {
        $group = new self("AND");
        $builder($group);
        $this->items[] = $group;
        return $this;
    }

    public function orGroup(callable $builder): self
    {
        $group = new self("OR");
        $builder($group);
        $this->items[] = $group;
        return $this;
    }

    /**
     * @return array<int, Condition|CriteriaExpression|self>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
