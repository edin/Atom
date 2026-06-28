<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Migration;

use Atom\Database\DatabaseConnection;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Migration\DatabaseMigrationRepository;
use Atom\Database\Migration\Driver\PostgresMigrationRepositoryDriver;
use Atom\Database\Migration\Driver\SqliteMigrationRepositoryDriver;
use PHPUnit\Framework\TestCase;

final class DatabaseMigrationRepositoryTest extends TestCase
{
    public function testCreatesRepositoryTable(): void
    {
        $repository = $this->repository();

        $this->assertInstanceOf(SqliteMigrationRepositoryDriver::class, $repository->driver());
        $this->assertFalse($repository->exists());

        $repository->create();

        $this->assertTrue($repository->exists());
        $this->assertSame([], $repository->applied());
        $this->assertSame(0, $repository->latestBatch());
    }

    public function testRecordsAndListsAppliedMigrations(): void
    {
        $repository = $this->repository();

        $repository->record("2026_01_01_000001_create_users", 1);
        $repository->record("2026_01_01_000002_create_posts", 1);
        $repository->record("2026_01_02_000001_add_email_to_users", 2);

        $this->assertSame([
            "2026_01_01_000001_create_users",
            "2026_01_01_000002_create_posts",
            "2026_01_02_000001_add_email_to_users",
        ], $repository->applied());
        $this->assertSame(2, $repository->latestBatch());
        $this->assertSame(["2026_01_02_000001_add_email_to_users"], $repository->batch(2));
        $this->assertSame([
            "2026_01_01_000002_create_posts",
            "2026_01_01_000001_create_users",
        ], $repository->batch(1));
    }

    public function testDeletesMigrationRecord(): void
    {
        $repository = $this->repository();

        $repository->record("create_users", 1);
        $repository->record("create_posts", 1);
        $repository->delete("create_users");

        $this->assertSame(["create_posts"], $repository->applied());
    }

    public function testDeleteBeforeCreateIsIgnored(): void
    {
        $repository = $this->repository();

        $repository->delete("missing");

        $this->assertFalse($repository->exists());
    }

    public function testSupportsCustomTableName(): void
    {
        $connection = $this->connection();
        $repository = new DatabaseMigrationRepository($connection, "atom_migrations");

        $repository->record("create_users", 1);

        $this->assertSame(1, (int) $connection->scalar("SELECT COUNT(*) FROM atom_migrations"));
    }

    public function testCanUseExplicitRepositoryDriver(): void
    {
        $connection = $this->connection();
        $repository = new DatabaseMigrationRepository(
            $connection,
            "migrations",
            new SqliteMigrationRepositoryDriver()
        );

        $repository->record("create_users", 1);

        $this->assertSame(["create_users"], $repository->applied());
    }

    public function testPostgresRepositoryDriverSql(): void
    {
        $driver = new PostgresMigrationRepositoryDriver();

        $this->assertStringContainsString("information_schema.tables", $driver->tableExistsSql());
        $this->assertStringContainsString("current_schema()", $driver->tableExistsSql());
        $this->assertSame('"migrations"', $driver->quoteIdentifier("migrations"));
        $this->assertSame('SELECT migration FROM "migrations" ORDER BY batch ASC, migration ASC', $driver->selectAppliedSql("migrations"));
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS "migrations"', $driver->createTableSql("migrations"));
        $this->assertStringContainsString("applied_at TIMESTAMP NOT NULL", $driver->createTableSql("migrations"));
    }

    private function repository(): DatabaseMigrationRepository
    {
        return new DatabaseMigrationRepository($this->connection());
    }

    private function connection(): DatabaseConnection
    {
        return new DatabaseConnection(SqliteDriver::memory());
    }
}
