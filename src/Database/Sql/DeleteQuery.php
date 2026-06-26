<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final class DeleteQuery implements SqlQuery
{
    private ?Table $table = null;
    private WhereGroup $where;
    private ?int $limit = null;

    public function __construct()
    {
        $this->where = new WhereGroup();
    }

    public function from(string $table): self
    {
        $this->table = Table::from($table);
        return $this;
    }

    public function where(string $column, mixed $value): self
    {
        $this->where->where($column, $value);
        return $this;
    }

    public function orWhere(string $column, mixed $value): self
    {
        $this->where->orWhere($column, $value);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function whereExp(string $expression, array $parameters = []): self
    {
        $this->where->whereExp($expression, $parameters);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function getTable(): ?Table
    {
        return $this->table;
    }

    public function getWhere(): WhereGroup
    {
        return $this->where;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
