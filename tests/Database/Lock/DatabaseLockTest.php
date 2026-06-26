<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Lock;

use Atom\Database\Lock\FileDatabaseLockManager;
use PHPUnit\Framework\TestCase;

final class DatabaseLockTest extends TestCase
{
    public function testFileLockCanBeAcquiredAndReleased(): void
    {
        $manager = new FileDatabaseLockManager($this->tempDirectory());

        $lock = $manager->acquire("migrations");

        $this->assertNotNull($lock);
        $this->assertNull($manager->acquire("migrations"));

        $lock->release();

        $this->assertNotNull($manager->acquire("migrations"));
    }

    public function testReleaseIsIdempotent(): void
    {
        $manager = new FileDatabaseLockManager($this->tempDirectory());
        $lock = $manager->acquire("migrations");

        $this->assertNotNull($lock);

        $lock->release();
        $lock->release();

        $this->assertNotNull($manager->acquire("migrations"));
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_locks_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}
