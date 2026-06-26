<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Migration;

use Atom\Database\DatabaseConnection;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Migration\DatabaseMigrationLockManager;
use Atom\Database\Migration\Driver\SqliteMigrationLockDriver;
use PHPUnit\Framework\TestCase;

final class DatabaseMigrationLockManagerTest extends TestCase
{
    public function testAcquiresAndReleasesDefaultLock(): void
    {
        $manager = $this->manager();

        $this->assertFalse($manager->exists());
        $this->assertFalse($manager->isLocked());

        $this->assertTrue($manager->acquire());
        $this->assertTrue($manager->exists());
        $this->assertTrue($manager->isLocked());

        $manager->release();

        $this->assertFalse($manager->isLocked());
    }

    public function testAcquireReturnsFalseWhenLockAlreadyExists(): void
    {
        $manager = $this->manager();

        $this->assertTrue($manager->acquire());
        $this->assertFalse($manager->acquire());
        $this->assertTrue($manager->isLocked());
    }

    public function testNamedLocksAreIndependent(): void
    {
        $manager = $this->manager();

        $this->assertTrue($manager->acquire("migrations"));
        $this->assertFalse($manager->isLocked("seed"));

        $this->assertTrue($manager->acquire("seed"));
        $this->assertTrue($manager->isLocked("migrations"));
        $this->assertTrue($manager->isLocked("seed"));

        $manager->release("seed");

        $this->assertTrue($manager->isLocked("migrations"));
        $this->assertFalse($manager->isLocked("seed"));
    }

    public function testCanUseExplicitLockDriver(): void
    {
        $manager = new DatabaseMigrationLockManager(
            new DatabaseConnection(SqliteDriver::memory()),
            "custom_locks",
            new SqliteMigrationLockDriver()
        );

        $this->assertTrue($manager->acquire());
        $this->assertInstanceOf(SqliteMigrationLockDriver::class, $manager->driver());
    }

    private function manager(): DatabaseMigrationLockManager
    {
        return new DatabaseMigrationLockManager(new DatabaseConnection(SqliteDriver::memory()));
    }
}

