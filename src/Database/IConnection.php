<?php

namespace Atom\Database;

use Atom\Database\Query\Query;

interface IConnection
{
    // public const MySQL = "mysql";
    // public const SQLite = "sqlite";
    // public function execute(Query $query);
    public function compileQuery(Query $query);
    public function beginTransaction(): ITransaction;
    public function executeSql(string $sql, array $parameters);
    public function queryAll(string $sql, array $parameters);
    public function queryScalar(string $sql, array $parameters);
}

interface IQueryCompiler
{
    public function compileQuery(Query $query); // Command
}

interface ITransaction
{
    public function commit();
}
