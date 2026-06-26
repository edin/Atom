<?php

declare(strict_types=1);

namespace Atom\Database\Driver;

use Atom\Database\Migration\Driver\SqliteMigrationRepositoryDriver;
use Atom\Database\Migration\Driver\SqliteMigrationLockDriver;
use Atom\Database\Migration\Driver\MigrationLockDriverInterface;
use Atom\Database\Migration\Driver\MigrationRepositoryDriverInterface;
use Atom\Database\DatabaseConnection;
use Atom\Database\Schema\Compiler\SchemaCompilerInterface;
use Atom\Database\Schema\Compiler\SqliteSchemaCompiler;
use Atom\Database\Schema\Inspector\SchemaInspectorInterface;
use Atom\Database\Schema\Inspector\SqliteSchemaInspector;
use Atom\Database\Schema\Reset\DatabaseResetterInterface;
use Atom\Database\Schema\Reset\SqliteDatabaseResetter;
use Atom\Database\Sql\Compiler\QueryCompiler;
use Atom\Database\Sql\Compiler\SqliteCompiler;

final class SqliteDriver extends AbstractPdoDriver
{
    /**
     * @param array<int, mixed> $options
     */
    public function __construct(string $path, array $options = [])
    {
        parent::__construct("sqlite:{$path}", null, null, $options);
    }

    public static function memory(array $options = []): self
    {
        return new self(":memory:", $options);
    }

    public function compiler(): QueryCompiler
    {
        return new SqliteCompiler();
    }

    public function schemaCompiler(): SchemaCompilerInterface
    {
        return new SqliteSchemaCompiler();
    }

    public function schemaInspector(DatabaseConnection $connection): SchemaInspectorInterface
    {
        return new SqliteSchemaInspector($connection);
    }

    public function migrationRepositoryDriver(): MigrationRepositoryDriverInterface
    {
        return new SqliteMigrationRepositoryDriver();
    }

    public function migrationLockDriver(): MigrationLockDriverInterface
    {
        return new SqliteMigrationLockDriver();
    }

    public function resetter(): DatabaseResetterInterface
    {
        return new SqliteDatabaseResetter();
    }
}
