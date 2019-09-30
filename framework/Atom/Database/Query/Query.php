<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Table;

abstract class Query
{
    protected $table = null;
    protected $isDistinct = null;
    protected $isExists = null;
    protected $limit = null;
    protected $offset = null;
    protected $columns = [];
    protected $unions = [];
    protected $joins = [];
    protected $where = [];
    protected $having = [];
    protected $orderBy = [];
    protected $groupBy = [];

    public function from(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }

    public function show()
    {
        print_r($this);
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
