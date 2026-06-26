<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Driver;

use Atom\Database\DatabaseDriver;
use Atom\Database\Driver\MySqlDriver;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Migration\Driver\MySqlMigrationLockDriver;
use Atom\Database\Migration\Driver\MySqlMigrationRepositoryDriver;
use Atom\Database\Migration\Driver\SqliteMigrationLockDriver;
use Atom\Database\Migration\Driver\SqliteMigrationRepositoryDriver;
use Atom\Database\Schema\Compiler\MySqlSchemaCompiler;
use Atom\Database\Schema\Compiler\SqliteSchemaCompiler as DatabaseSqliteSchemaCompiler;
use Atom\Database\Schema\Inspector\MySqlSchemaInspector;
use Atom\Database\Schema\Inspector\SqliteSchemaInspector;
use Atom\Database\Schema\Reset\MySqlDatabaseResetter;
use Atom\Database\Schema\Reset\SqliteDatabaseResetter;
use Atom\Database\Sql\Compiler\MySqlCompiler;
use Atom\Database\Sql\Compiler\SqliteCompiler;
use Atom\Database\DatabaseConnection;
use PHPUnit\Framework\TestCase;

final class DatabaseDriverTest extends TestCase
{
    public function testMySqlDriverProvidesCompiler(): void
    {
        $driver = new MySqlDriver("atom", "localhost", "root", "secret");

        $this->assertInstanceOf(DatabaseDriver::class, $driver);
        $this->assertInstanceOf(MySqlCompiler::class, $driver->compiler());
        $this->assertInstanceOf(MySqlSchemaCompiler::class, $driver->schemaCompiler());
        $this->assertInstanceOf(MySqlSchemaInspector::class, $driver->schemaInspector(new DatabaseConnection($driver)));
        $this->assertInstanceOf(MySqlMigrationRepositoryDriver::class, $driver->migrationRepositoryDriver());
        $this->assertInstanceOf(MySqlMigrationLockDriver::class, $driver->migrationLockDriver());
        $this->assertInstanceOf(MySqlDatabaseResetter::class, $driver->resetter());
    }

    public function testSqliteDriverProvidesCompiler(): void
    {
        $driver = SqliteDriver::memory();

        $this->assertInstanceOf(DatabaseDriver::class, $driver);
        $this->assertInstanceOf(SqliteCompiler::class, $driver->compiler());
        $this->assertInstanceOf(DatabaseSqliteSchemaCompiler::class, $driver->schemaCompiler());
        $this->assertInstanceOf(SqliteSchemaInspector::class, $driver->schemaInspector(new DatabaseConnection($driver)));
        $this->assertInstanceOf(SqliteMigrationRepositoryDriver::class, $driver->migrationRepositoryDriver());
        $this->assertInstanceOf(SqliteMigrationLockDriver::class, $driver->migrationLockDriver());
        $this->assertInstanceOf(SqliteDatabaseResetter::class, $driver->resetter());
    }
}
