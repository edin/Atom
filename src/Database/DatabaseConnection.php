<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Sql\Command;
use Atom\Database\Sql\SqlQuery;
use PDO;
use Throwable;

final class DatabaseConnection
{
    private ?PDO $pdo = null;

    public function __construct(private readonly DatabaseDriver $driver)
    {
    }

    public function driver(): DatabaseDriver
    {
        return $this->driver;
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= $this->driver->connect();
    }

    public function compile(SqlQuery $query): Command
    {
        return $this->driver->compiler()->compile($query);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function execute(string|SqlQuery|Command $query, array $parameters = []): int
    {
        $statement = $this->prepare($query, $parameters);
        $statement->execute();
        $count = $statement->rowCount();
        $statement->closeCursor();

        return $count;
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<int, array<string, mixed>>
     */
    public function all(string|SqlQuery|Command $query, array $parameters = []): array
    {
        $statement = $this->prepare($query, $parameters);
        $statement->execute();
        $rows = $statement->fetchAll();
        $statement->closeCursor();

        return $rows;
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>|null
     */
    public function first(string|SqlQuery|Command $query, array $parameters = []): ?array
    {
        $statement = $this->prepare($query, $parameters);
        $statement->execute();
        $row = $statement->fetch();
        $statement->closeCursor();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function scalar(string|SqlQuery|Command $query, array $parameters = []): mixed
    {
        $statement = $this->prepare($query, $parameters);
        $statement->execute();
        $value = $statement->fetchColumn();
        $statement->closeCursor();

        return $value;
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo()->lastInsertId($name);
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function prepare(string|SqlQuery|Command $query, array $parameters = []): \PDOStatement
    {
        $command = match (true) {
            is_string($query) => new Command($query, $parameters),
            $query instanceof Command => $parameters === []
                ? $query
                : new Command($query->sql, array_replace($query->parameters, $parameters)),
            default => $this->compile($query),
        };
        $statement = $this->pdo()->prepare($command->sql);

        foreach ($command->parameters as $name => $value) {
            $statement->bindValue($name, $value, $this->parameterType($value));
        }

        return $statement;
    }

    private function parameterType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}
