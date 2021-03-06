<?php

namespace Atom\Database\Interfaces;

use Atom\Database\Query\Command;
use Atom\Database\Interfaces\ITransaction;
use Atom\Database\Query\Query;

interface IConnection
{
    public function compileQuery(Query $query): Command;
    public function beginTransaction(): ITransaction;
    public function execute(string $sql, array $parameters = []): bool;
    public function queryAll(string $sql, array $parameters = []): array;
    public function queryScalar(string $sql, array $parameters = []);
    public function lastInsertId();
}
