<?php

namespace Atom\Database\Connector;

use PDO;
use Atom\Database\Interfaces\IQueryCompiler;
use Atom\Database\Query\Compilers\MySqlCompiler;

class MySqlConnector extends AbstractConnector
{
    public function open(): PDO
    {
        $dsn = "mysql:dbname={$this->database};host={$this->host}";
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
        return new MySqlCompiler();
    }
}
