<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

use Atom\Database\DatabaseConnection;
use Atom\Database\Migration\Driver\MigrationLockDriverInterface;
use DateTimeImmutable;
use PDOException;

final readonly class DatabaseMigrationLockManager implements MigrationLockManagerInterface
{
    private MigrationLockDriverInterface $driver;

    public function __construct(
        private DatabaseConnection $connection,
        private string $table = "migration_locks",
        ?MigrationLockDriverInterface $driver = null
    ) {
        $this->driver = $driver ?? $this->connection->driver()->migrationLockDriver();
    }

    public function acquire(string $name = "migrations"): bool
    {
        if (!$this->exists()) {
            $this->create();
        }

        try {
            $this->connection->execute(
                $this->driver->acquireSql($this->table),
                [
                    ":name" => $name,
                    ":acquired_at" => (new DateTimeImmutable())->format("Y-m-d H:i:s"),
                ]
            );

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function release(string $name = "migrations"): void
    {
        if (!$this->exists()) {
            return;
        }

        $this->connection->execute(
            $this->driver->releaseSql($this->table),
            [":name" => $name]
        );
    }

    public function isLocked(string $name = "migrations"): bool
    {
        if (!$this->exists()) {
            return false;
        }

        return (int) $this->connection->scalar(
            $this->driver->isLockedSql($this->table),
            [":name" => $name]
        ) > 0;
    }

    public function exists(): bool
    {
        return (int) $this->connection->scalar(
            $this->driver->tableExistsSql(),
            [":table" => $this->table]
        ) > 0;
    }

    public function create(): void
    {
        $this->connection->execute($this->driver->createTableSql($this->table));
    }

    public function driver(): MigrationLockDriverInterface
    {
        return $this->driver;
    }
}
