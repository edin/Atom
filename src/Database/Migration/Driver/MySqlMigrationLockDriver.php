<?php

declare(strict_types=1);

namespace Atom\Database\Migration\Driver;


final readonly class MySqlMigrationLockDriver implements MigrationLockDriverInterface
{
    public function tableExistsSql(): string
    {
        return "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table";
    }

    public function createTableSql(string $table): string
    {
        return "CREATE TABLE IF NOT EXISTS {$this->quoteIdentifier($table)} (
            name VARCHAR(255) NOT NULL PRIMARY KEY,
            acquired_at DATETIME NOT NULL
        )";
    }

    public function isLockedSql(string $table): string
    {
        return "SELECT COUNT(*) FROM {$this->quoteIdentifier($table)} WHERE name = :name";
    }

    public function acquireSql(string $table): string
    {
        return "INSERT INTO {$this->quoteIdentifier($table)} (name, acquired_at) VALUES (:name, :acquired_at)";
    }

    public function releaseSql(string $table): string
    {
        return "DELETE FROM {$this->quoteIdentifier($table)} WHERE name = :name";
    }

    private function quoteIdentifier(string $identifier): string
    {
        return "`" . str_replace("`", "``", $identifier) . "`";
    }
}
