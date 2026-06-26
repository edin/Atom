<?php

declare(strict_types=1);

namespace Atom\Database\Migration;

interface MigrationLockManagerInterface
{
    public function acquire(string $name = "migrations"): bool;

    public function release(string $name = "migrations"): void;

    public function isLocked(string $name = "migrations"): bool;
}

