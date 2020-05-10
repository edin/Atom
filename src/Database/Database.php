<?php

namespace Atom\Database;

use Atom\Database\Query\Command;
use Atom\Database\Interfaces\IConnection;
use Atom\Database\Query\Query;
use Atom\Database\Query\SelectQuery;

class Database
{
    private $writeConnection;
    private $readConnection;

    public function __construct(IConnection $connection /*, IConnection $readConnection = null*/)
    {
        $this->writeConnection = $connection;
        $this->readConnection = /*$readConnection ??*/ $connection;
    }

    public function getReadConnection(): IConnection
    {
        return $this->readConnection;
    }

    public function getWriteConnection(): IConnection
    {
        return $this->writeConnection;
    }

    public function compileQuery(Query $query): Command
    {
        return $this->getConnection($query)->compileQuery($query);
    }

    public function getConnection(Query $query): IConnection
    {
        if ($query instanceof SelectQuery) {
            return $this->readConnection;
        }
        return $this->writeConnection;
    }
}
