<?php

namespace Atom\Database\Connector;

use PDO;
use Atom\Database\Interfaces\IQueryCompiler;
use Atom\Database\Query\Compilers\PgSqlCompiler;

class PgSqlConnector extends AbstractConnector
{
    public function open(): PDO
    {
        $dsn = "pgsql:dbname={$this->database};host={$this->host}";
        $this->connection = new PDO($dsn, $this->user, $this->password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        foreach ($this->attributes as $key => $value) {
            $this->connection->setAttribute($key, $value);
        }
        return $this->connection;
    }

    public function getQueryCompiler(): IQueryCompiler
    {
        return new PgSqlCompiler();
    }
}
