<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

use Atom\Database\DatabaseConnection;
use Atom\Database\Migration\Driver\MigrationRepositoryDriverInterface;
use DateTimeImmutable;

final readonly class DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    private MigrationRepositoryDriverInterface $driver;

    public function __construct(
        private DatabaseConnection $connection,
        private string $table = "migrations",
        ?MigrationRepositoryDriverInterface $driver = null
    ) {
        $this->driver = $driver ?? $this->connection->driver()->migrationRepositoryDriver();
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

    public function applied(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->connection->all($this->driver->selectAppliedSql($this->table));

        return array_map(
            static fn(array $row): string => (string) $row["migration"],
            $rows
        );
    }

    public function latestBatch(): int
    {
        if (!$this->exists()) {
            return 0;
        }

        return (int) ($this->connection->scalar($this->driver->latestBatchSql($this->table)) ?? 0);
    }

    public function batch(int $batch): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->connection->all(
            $this->driver->selectBatchSql($this->table),
            [":batch" => $batch]
        );

        return array_map(
            static fn(array $row): string => (string) $row["migration"],
            $rows
        );
    }

    public function record(string $migration, int $batch): void
    {
        if (!$this->exists()) {
            $this->create();
        }

        $this->connection->execute(
            $this->driver->insertSql($this->table),
            [
                ":migration" => $migration,
                ":batch" => $batch,
                ":applied_at" => (new DateTimeImmutable())->format("Y-m-d H:i:s"),
            ]
        );
    }

    public function delete(string $migration): void
    {
        if (!$this->exists()) {
            return;
        }

        $this->connection->execute(
            $this->driver->deleteSql($this->table),
            [":migration" => $migration]
        );
    }

    public function driver(): MigrationRepositoryDriverInterface
    {
        return $this->driver;
    }
}
