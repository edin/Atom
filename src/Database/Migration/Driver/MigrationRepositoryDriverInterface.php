<?php

declare(strict_types=1);

namespace Atom\Database\Migration\Driver;

interface MigrationRepositoryDriverInterface
{
    public function tableExistsSql(): string;

    public function createTableSql(string $table): string;

    public function selectAppliedSql(string $table): string;

    public function latestBatchSql(string $table): string;

    public function selectBatchSql(string $table): string;

    public function insertSql(string $table): string;

    public function deleteSql(string $table): string;

    public function quoteIdentifier(string $identifier): string;
}
