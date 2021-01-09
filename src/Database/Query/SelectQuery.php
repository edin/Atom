<?php

declare(strict_types=1);

namespace Atom\Database\Query;

use Atom\Collections\PagedCollection;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\Join;
use Atom\Database\Query\Ast\SortOrder;
use Atom\Database\Query\Ast\Table;
use Closure;

final class SelectQuery extends Query
{
    use QueryTrait;

    public function from(string $table): self
    {
        $this->from = Table::fromValue($table);
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
                $this->columns[] = Column::fromAlias($key, $value);
            }
        }
        return $this;
    }

    public function union(SelectQuery $query): self
    {
        $this->unions[] = $query;
        return $this;
    }

    public function join(string $table, Closure $join): self
    {
        $this->joins[] = Join::create(Join::Join, $table, $this->buildJoinCriteria($join));
        return $this;
    }

    public function leftJoin(string $table, Closure $join): self
    {
        $this->joins[] = Join::create(Join::LeftJoin, $table, $this->buildJoinCriteria($join));
        return $this;
    }

    public function rightJoin(string $table, Closure $join): self
    {
        $this->joins[] = Join::create(Join::RightJoin, $table, $this->buildJoinCriteria($join));
        return $this;
    }

    private function buildJoinCriteria(Closure $joinBuilder): Criteria
    {
        $criteria = new Criteria();
        $joinBuilder($criteria);
        return $criteria;
    }

    public function having(Closure $criteriaBuilder): self
    {
        $criteria = new Criteria();
        $criteriaBuilder($criteria);
        $this->having = $criteria;
        return $this;
    }

    public function orderBy(string $field, string $order = SortOrder::ASC): self
    {
        $this->orderBy[] = SortOrder::fromColumn($field, $order);
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

    public function groupBy(string $field): self
    {
        $this->groupBy[] = Column::fromValue($field);
        return $this;
    }

    public function findAll()
    {
        return $this->compileQuery()->findAll();
    }

    public function queryAll()
    {
        return $this->compileQuery()->queryAll();
    }

    public function queryScalar()
    {
        return $this->compileQuery()->queryScalar();
    }

    public function getRowCount(): int
    {
        $count = (int) $this->count()->queryScalar();
        $this->count = null;
        return $count;
    }

    public function toPagedCollection(int $page, int $size = 20): PagedCollection
    {
        $page = ($page > 0) ? $page : 1;
        $skip = ($page - 1) * $size;

        $count = $this->getRowCount();
        $query = $this->limit($size)->skip($skip);
        $items = $query->findAll();

        $collection = new PagedCollection($items);
        $collection->setTotalCount($count);
        $collection->setPageSize($size);
        $collection->setCurrentPage($page);
        $collection->setTotalPages((int) ceil($count / $size));
        return $collection;
    }
}
