<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

interface MigrationRepositoryInterface
{
    public function exists(): bool;

    public function create(): void;

    /**
     * @return string[]
     */
    public function applied(): array;

    public function latestBatch(): int;

    /**
     * @return string[]
     */
    public function batch(int $batch): array;

    public function record(string $migration, int $batch): void;

    public function delete(string $migration): void;
}
