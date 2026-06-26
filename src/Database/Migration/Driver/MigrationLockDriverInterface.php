<?php

declare(strict_types=1);

namespace Atom\Database\Migration\Driver;

interface MigrationLockDriverInterface
{
    public function tableExistsSql(): string;

    public function createTableSql(string $table): string;

    public function isLockedSql(string $table): string;

    public function acquireSql(string $table): string;

    public function releaseSql(string $table): string;
}
