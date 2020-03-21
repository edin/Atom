<?php

class Connection
{
    public const MySQL = "mysql";
    public const SqLite = "sqlite";
    private $db = null;

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
        if ($this->db === null) {
            $dsn = "{$this->driver}:dbname={$this->database};host={$this->host}";
            $this->db = new PDO($dsn, $this->user, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $this->db;
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

    public function execute(string $sql, array $params = []): bool
    {
        $command = $this->prepare($sql, $params);
        return $command->execute();
    }

    public function insert(string $sql, array $params = [])
    {
        $command = $this->prepare($sql, $params);
        $command->execute();
        return $this->getDb()->lastInsertId();
    }

    public function queryAll(string $sql, array $params = []): array
    {
        $command = $this->prepare($sql, $params);
        $command->execute();
        return $command->fetchAll();
    }

    public function queryScalar(string $sql, array $params = [])
    {
        $command = $this->prepare($sql, $params);
        return $command->fetchColumn();
    }
}
