<?php

namespace Atom\Database\Query;

abstract class Query
{
    private $table = null;
    private $joins = [];
    private $where = [];

    public function from(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }

    public function show()
    {
        var_dump($this);
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
