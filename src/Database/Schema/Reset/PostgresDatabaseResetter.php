<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Reset;

use Atom\Database\DatabaseConnection;

final readonly class PostgresDatabaseResetter implements DatabaseResetterInterface
{
    public function reset(DatabaseConnection $connection): void
    {
        $tables = $connection->all(
            "SELECT tablename FROM pg_tables WHERE schemaname = current_schema()"
        );

        foreach ($tables as $table) {
            $connection->execute("DROP TABLE " . $this->name((string) $table["tablename"]) . " CASCADE");
        }
    }

    private function name(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
