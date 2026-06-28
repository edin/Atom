<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Inspector;

use Atom\Database\DatabaseConnection;

final readonly class PostgresSchemaInspector implements SchemaInspectorInterface
{
    public function __construct(private DatabaseConnection $connection)
    {
    }

    public function hasTable(string $table): bool
    {
        return (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = :table",
            [":table" => $table]
        ) > 0;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = :table AND column_name = :column",
            [":table" => $table, ":column" => $column]
        ) > 0;
    }

    public function columns(string $table): array
    {
        $rows = $this->connection->all(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = current_schema() AND table_name = :table
             ORDER BY ordinal_position",
            [":table" => $table]
        );

        return array_map(static fn(array $row): string => (string) $row["column_name"], $rows);
    }
}
