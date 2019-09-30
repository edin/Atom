<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\Join;

final class SelectQuery extends Query
{
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
        $this->joins[] = Join::create(Join::Join, $table, $join);
        return $this;
    }

    public function leftJoin(string $table, callable $join): self
    {
        $this->joins[] = Join::create(Join::LeftJoin, $table, $join);
        return $this;
    }

    public function rightJoin(string $table, callable $join): self
    {
        $this->joins[] = Join::create(Join::RightJoin, $table, $join);
        return $this;
    }
}
