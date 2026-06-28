<?php

declare(strict_types=1);

namespace Atom\Database\Migration\Driver;

final readonly class PostgresMigrationRepositoryDriver implements MigrationRepositoryDriverInterface
{
    public function tableExistsSql(): string
    {
        return "SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = current_schema() AND table_name = :table";
    }

    public function createTableSql(string $table): string
    {
        return "CREATE TABLE IF NOT EXISTS {$this->quoteIdentifier($table)} (
            migration VARCHAR(255) NOT NULL PRIMARY KEY,
            batch INTEGER NOT NULL,
            applied_at TIMESTAMP NOT NULL
        )";
    }

    public function selectAppliedSql(string $table): string
    {
        return "SELECT migration FROM {$this->quoteIdentifier($table)} ORDER BY batch ASC, migration ASC";
    }

    public function latestBatchSql(string $table): string
    {
        return "SELECT MAX(batch) FROM {$this->quoteIdentifier($table)}";
    }

    public function selectBatchSql(string $table): string
    {
        return "SELECT migration FROM {$this->quoteIdentifier($table)} WHERE batch = :batch ORDER BY migration DESC";
    }

    public function insertSql(string $table): string
    {
        return "INSERT INTO {$this->quoteIdentifier($table)} (migration, batch, applied_at)
            VALUES (:migration, :batch, :applied_at)";
    }

    public function deleteSql(string $table): string
    {
        return "DELETE FROM {$this->quoteIdentifier($table)} WHERE migration = :migration";
    }

    public function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
