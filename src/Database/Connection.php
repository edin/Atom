<?php

namespace Atom\Database;

use PDO;
use PDOStatement;
use Atom\Database\Query\Query;
use Atom\Database\Query\Command;
use Atom\Database\Query\Parameter;
use Atom\Database\Interfaces\IConnection;
use Atom\Database\Interfaces\ITransaction;
use Atom\Database\Interfaces\IDatabaseConnector;

class Connection implements IConnection
{
    private $connector;
    private $connection;

    public function __construct(IDatabaseConnector $connector)
    {
        $this->connector = $connector;
    }

    private function getConnection(): PDO
    {
        if ($this->connection == null) {
            $this->connection = $this->connector->open();
        }
        return $this->connection;
    }

    public function close(): void
    {
        $this->connection = null;
        $this->connector->close();
    }

    private function prepare(string $sql, array $params) : PDOStatement
    {
        $command = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            if ($value instanceof Parameter) {
                $parameter = $value;
                $command->bindValue($parameter->getName(), $parameter->getValue(), $parameter->getType());
            } else {
                $command->bindValue($key, $value);
            }
        }
        return $command;
    }

    public function beginTransaction(): ITransaction
    {
        return new Transaction($this->getConnection());
    }

    public function compileQuery(Query $query): Command
    {
        $compiler = $this->connector->getQueryCompiler();
        $command = $compiler->compileQuery($query);
        $command->setConnection($this);
        return $command;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $command = $this->prepare($sql, $params);
        $result = $command->execute();
        $command->closeCursor();
        return $result;
    }

    public function queryAll(string $sql, array $params = []): array
    {
        $command = $this->prepare($sql, $params);
        $command->execute();
        $result = $command->fetchAll();
        $command->closeCursor();
        return $result;
    }

    public function queryScalar(string $sql, array $params = [])
    {
        $command = $this->prepare($sql, $params);
        $result = $command->fetchColumn();
        $command->closeCursor();
        return $result;
    }
}
