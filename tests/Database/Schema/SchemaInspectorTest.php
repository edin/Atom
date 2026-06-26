<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Schema;

use Atom\Database\DatabaseConnection;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Schema\Inspector\SqliteSchemaInspector;
use PHPUnit\Framework\TestCase;

final class SchemaInspectorTest extends TestCase
{
    public function testSqliteInspectorReadsTablesAndColumns(): void
    {
        $connection = new DatabaseConnection(SqliteDriver::memory());
        $connection->execute("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, name TEXT)");
        $inspector = new SqliteSchemaInspector($connection);

        $this->assertTrue($inspector->hasTable("users"));
        $this->assertFalse($inspector->hasTable("missing"));
        $this->assertTrue($inspector->hasColumn("users", "email"));
        $this->assertFalse($inspector->hasColumn("users", "missing"));
        $this->assertSame(["id", "email", "name"], $inspector->columns("users"));
        $this->assertSame([], $inspector->columns("missing"));
    }
}
