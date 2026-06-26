<?php

declare(strict_types=1);

namespace Atom\Database\Lock;

use Atom\Database\DatabaseConnection;

final readonly class MySqlDatabaseLockManager implements DatabaseLockManagerInterface
{
    public function __construct(private DatabaseConnection $connection)
    {
    }

    public function acquire(string $name): ?DatabaseLock
    {
        $acquired = (int) $this->connection->scalar(
            "SELECT GET_LOCK(:name, 0)",
            [":name" => $name]
        ) === 1;

        if (!$acquired) {
            return null;
        }

        return new DatabaseLock(fn() => $this->connection->scalar(
            "SELECT RELEASE_LOCK(:name)",
            [":name" => $name]
        ));
    }
}
