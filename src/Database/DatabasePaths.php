<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Config\Options;

#[Options(prefix: "DB_PATH_")]
final readonly class DatabasePaths
{
    public function __construct(
        public string $root = "@root",
        public string $migrations = "@app/Database/Migrations",
        public string $seeders = "@app/Database/Seeders"
    ) {
    }
}
