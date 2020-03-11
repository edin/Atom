<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\Join;
use Atom\Database\Query\Ast\SortOrder;
use Atom\Database\Query\Ast\Table;

final class SelectQuery extends Query
{
    public function from(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }

    public function distinct(): self
    {
        $this->isDistinct = true;
        return $this;
    }

    public function exists(): self
    {
        $this->isExists = true;
        return $this;
    }

    public function notExists(): self
    {
        $this->isExists = false;
        return $this;
    }

    public function skip(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function count(string $column = "*"): self
    {
        $this->count = $column;
        return $this;
    }

    public function columns(array $columns): self
    {
        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $this->columns[] = Column::fromValue($value);
            } else {
                $column =  Column::fromValue($key);
                $column->expression = $value;
                $this->columns[] = $column;
            }
        }
        return $this;
    }

    public function union(SelectQuery $query): self
    {
        $this->unions[] = $query;
        return $this;
    }

    public function join(string $table, callable $join): self
    {
        $this->joins[] = Join::create(Join::Join, $table, $this->buildJoinCriteria($join));
        return $this;
    }

    public function leftJoin(string $table, callable $join): self
    {
        $this->joins[] = Join::create(Join::LeftJoin, $table, $this->buildJoinCriteria($join));
        return $this;
    }

    public function rightJoin(string $table, callable $join): self
    {
        $this->joins[] = Join::create(Join::RightJoin, $table, $this->buildJoinCriteria($join));
        return $this;
    }

    private function buildJoinCriteria(callable $joinBuilder): Criteria
    {
        $criteria = new Criteria();
        $joinBuilder($criteria);
        return $criteria;
    }

    public function having(callable $criteriaBuilder): self
    {
        $criteria = new Criteria();
        $criteriaBuilder($criteria);
        $this->having[] = $criteria;
        return $this;
    }

    public function orderBy(string $field, string $order = SortOrder::ASC): self
    {
        $this->orderBy[] = SortOrder::fromColumn($field, SortOrder::ASC);
        return $this;
    }

    public function orderByAsc(string $field): self
    {
        $this->orderBy[] = SortOrder::fromColumn($field, SortOrder::ASC);
        return $this;
    }

    public function orderByDesc(string $field): self
    {
        $this->orderBy[] = SortOrder::fromColumn($field, SortOrder::DESC);
        return $this;
    }
}
