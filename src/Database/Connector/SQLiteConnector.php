<?php

declare(strict_types=1);

namespace Atom\Database\Connector;

use PDO;
use Atom\Database\Interfaces\IQueryCompiler;
use Atom\Database\Query\Compilers\SQLiteCompiler;

class SQLiteConnector extends AbstractConnector
{
    public function open(): PDO
    {
        $dsn = "pqsql:dbname={$this->database};host={$this->host};username={$this->user};password={$this->password}";
        $this->connection = new PDO($dsn);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        foreach ($this->attributes as $key => $value) {
            $this->connection->setAttribute($key, $value);
        }
        return $this->connection;
    }

    public function getQueryCompiler(): IQueryCompiler
    {
        return new SQLiteCompiler();
    }
}
