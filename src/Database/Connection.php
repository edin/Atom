<?php

namespace Atom\Database;

use PDO;

class Connection
{
    public const MySQL = "mysql";
    public const SqLite = "sqlite";

    public function __construct(string $driver, string $host, string $user, string $password, string $database)
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }

    private function getDb(): PDO
    {
        $dsn = "{$this->driver}:dbname={$this->database};host={$this->host}";
        $db = new PDO($dsn, $this->user, $this->password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    }

    private function prepare(string $sql, array $params)
    {
        $db = $this->getDb();
        $command = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $command->bindValue($key, $value);
        }

        return $command;
    }

    public function queryAll(string $sql, array $params = []): array
    {
        $command = $this->prepare($sql, $params);
        return $command->fetchAll();
    }

    public function queryScalar(string $sql, array $params = [])
    {
        $command = $this->prepare($sql, $params);
        return $command->fetchColumn();
    }
}
