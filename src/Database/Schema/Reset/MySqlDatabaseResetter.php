<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Reset;

use Atom\Database\DatabaseConnection;

final readonly class MySqlDatabaseResetter implements DatabaseResetterInterface
{
    public function reset(DatabaseConnection $connection): void
    {
        $connection->execute("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($connection->all("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $row) {
            $table = (string) array_values($row)[0];
            $connection->execute("DROP TABLE " . $this->name($table));
        }

        $connection->execute("SET FOREIGN_KEY_CHECKS = 1");
    }

    private function name(string $name): string
    {
        return "`" . str_replace("`", "``", $name) . "`";
    }
}
