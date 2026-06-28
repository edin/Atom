<?php

declare(strict_types=1);

namespace Atom\Database\Lock;

use Atom\Database\DatabaseConnection;

final readonly class PostgresDatabaseLockManager implements DatabaseLockManagerInterface
{
    public function __construct(private DatabaseConnection $connection)
    {
    }

    public function acquire(string $name): ?DatabaseLock
    {
        $key = $this->key($name);
        $acquired = (bool) $this->connection->scalar(
            "SELECT pg_try_advisory_lock(:key)",
            [":key" => $key]
        );

        if (!$acquired) {
            return null;
        }

        return new DatabaseLock(fn() => $this->connection->scalar(
            "SELECT pg_advisory_unlock(:key)",
            [":key" => $key]
        ));
    }

    private function key(string $name): int
    {
        $hex = substr(hash("sha256", $name), 0, 15);

        return (int) hexdec($hex);
    }
}
