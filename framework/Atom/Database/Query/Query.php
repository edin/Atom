<?php

namespace Atom\Database\Query;

abstract class Query
{
    protected $table = null;
    protected $count = null;
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
    protected $values = null;

    public function getJoins()
    {
        return $this->joins;
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
