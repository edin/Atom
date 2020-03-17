<?php

namespace Atom\Database\Query;

abstract class Query
{
    protected $from = null;
    protected $table = null;
    protected $count = null;
    protected $isDistinct = null;
    protected $isExists = null;
    protected $limit = null;
    protected $offset = null;
    protected $columns = [];
    protected $unions = [];
    protected $joins = [];
    protected $where = null;
    protected $having = null;
    protected $orderBy = [];
    protected $groupBy = [];
    protected $values = null;

    public function getTable()
    {
        return $this->table;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getIsDistinct()
    {
        return $this->isDistinct;
    }

    public function getIsExists()
    {
        return $this->isExists;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getUnions(): array
    {
        return $this->unions;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getWhere(): ?Criteria
    {
        return $this->where;
    }

    public function getHaving(): ?Criteria
    {
        return $this->having;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getValues()
    {
        return $this->values;
    }

    public static function select(): SelectQuery
    {
        return new SelectQuery();
    }

    public static function delete(): DeleteQuery
    {
        return new DeleteQuery();
    }

    public static function update(): UpdateQuery
    {
        return new UpdateQuery();
    }

    public static function insert(): InsertQuery
    {
        return new InsertQuery();
    }
}
