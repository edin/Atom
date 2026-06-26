<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final class SelectQuery implements SqlQuery
{
    private ?Table $from = null;
    /** @var array<int, Column|SelectExpression> */
    private array $columns = [];
    /** @var Join[] */
    private array $joins = [];
    private WhereGroup $where;
    /** @var Sort[] */
    private array $orderBy = [];
    /** @var Column[] */
    private array $groupBy = [];
    private WhereGroup $having;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct()
    {
        $this->where = new WhereGroup();
        $this->having = new WhereGroup();
    }

    public function from(string $table): self
    {
        $this->from = Table::from($table);
        return $this;
    }

    public function columns(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->columns[] = Column::from($column);
        }

        return $this;
    }

    public function count(string $column = "*", ?string $alias = null): self
    {
        $this->columns[] = SelectExpression::count($column, $alias);
        return $this;
    }

    public function clearColumns(): self
    {
        $this->columns = [];
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

    /**
     * @param array<string, mixed> $parameters
     */
    public function orWhereExp(string $expression, array $parameters = []): self
    {
        $this->where->orWhereExp($expression, $parameters);
        return $this;
    }

    public function group(callable $builder): self
    {
        $this->where->group($builder);
        return $this;
    }

    public function orGroup(callable $builder): self
    {
        $this->where->orGroup($builder);
        return $this;
    }

    public function join(string $table, ?callable $builder = null): self
    {
        $join = Join::inner($table);
        if ($builder !== null) {
            $builder($join);
        }
        $this->joins[] = $join;
        return $this;
    }

    public function leftJoin(string $table, ?callable $builder = null): self
    {
        $join = Join::left($table);
        if ($builder !== null) {
            $builder($join);
        }
        $this->joins[] = $join;
        return $this;
    }

    public function rightJoin(string $table, ?callable $builder = null): self
    {
        $join = Join::right($table);
        if ($builder !== null) {
            $builder($join);
        }
        $this->joins[] = $join;
        return $this;
    }

    public function orderBy(string $column): self
    {
        $this->orderBy[] = Sort::asc($column);
        return $this;
    }

    public function orderByDesc(string $column): self
    {
        $this->orderBy[] = Sort::desc($column);
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        foreach ($columns as $column) {
            $this->groupBy[] = Column::from($column);
        }

        return $this;
    }

    public function having(string $column, mixed $value): self
    {
        $this->having->where($column, $value);
        return $this;
    }

    public function orHaving(string $column, mixed $value): self
    {
        $this->having->orWhere($column, $value);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function havingExp(string $expression, array $parameters = []): self
    {
        $this->having->whereExp($expression, $parameters);
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function orHavingExp(string $expression, array $parameters = []): self
    {
        $this->having->orWhereExp($expression, $parameters);
        return $this;
    }

    public function havingGroup(callable $builder): self
    {
        $this->having->group($builder);
        return $this;
    }

    public function orHavingGroup(callable $builder): self
    {
        $this->having->orGroup($builder);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getFrom(): ?Table
    {
        return $this->from;
    }

    /**
     * @return array<int, Column|SelectExpression>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return Join[]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * @return WhereGroup
     */
    public function getWhere(): WhereGroup
    {
        return $this->where;
    }

    /**
     * @return Sort[]
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return Column[]
     */
    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getHaving(): WhereGroup
    {
        return $this->having;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }
}
