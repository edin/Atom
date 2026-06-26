<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Inspector;

use Atom\Database\DatabaseConnection;

final readonly class SqliteSchemaInspector implements SchemaInspectorInterface
{
    public function __construct(private DatabaseConnection $connection)
    {
    }

    public function hasTable(string $table): bool
    {
        return (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = :table",
            [":table" => $table]
        ) > 0;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    public function columns(string $table): array
    {
        if (!$this->hasTable($table)) {
            return [];
        }

        $rows = $this->connection->all("PRAGMA table_info(" . $this->name($table) . ")");

        return array_map(static fn(array $row): string => (string) $row["name"], $rows);
    }

    private function name(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
