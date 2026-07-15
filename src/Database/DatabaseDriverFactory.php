<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Driver\MySqlDriver;
use Atom\Database\Driver\PostgresDriver;
use Atom\Database\Driver\SqliteDriver;

final readonly class DatabaseDriverFactory
{
    public function __construct(private string $root = "")
    {
    }

    public static function fromEnv(string $root = ""): DatabaseDriverInterface
    {
        return (new self($root))->create(DatabaseConfig::fromEnv());
    }

    public function create(DatabaseConfig $config): DatabaseDriverInterface
    {
        return match (strtolower($config->driver)) {
            "sqlite" => new SqliteDriver($this->path($config->database), $config->options),
            "mysql", "mariadb" => new MySqlDriver(
                $config->database,
                $config->host,
                $config->username,
                $config->password,
                $config->port,
                $config->charset,
                $config->options
            ),
            "pgsql", "postgres", "postgresql" => new PostgresDriver(
                $config->database,
                $config->host,
                $config->username,
                $config->password,
                $config->port,
                $config->options
            ),
            default => throw new DatabaseDriverFactoryException("Unsupported database driver '{$config->driver}'."),
        };
    }

    private function path(string $path): string
    {
        if ($path === ":memory:" || $this->root === "" || preg_match('/^(?:[A-Za-z]:[\/\\\\]|[\/\\\\])/', $path) === 1) {
            return $path;
        }

        return rtrim($this->root, "/\\") . "/" . ltrim($path, "/\\");
    }
}
