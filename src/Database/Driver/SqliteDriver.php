<?php

declare(strict_types=1);

namespace Atom\Database\Driver;

use Atom\Database\Migration\Driver\SqliteMigrationRepositoryDriver;
use Atom\Database\Migration\Driver\MigrationRepositoryDriverInterface;
use Atom\Database\DatabaseConnection;
use Atom\Database\Lock\DatabaseLockManagerInterface;
use Atom\Database\Lock\FileDatabaseLockManager;
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
    public function __construct(private string $path, array $options = [])
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

    public function lockManager(DatabaseConnection $connection): DatabaseLockManagerInterface
    {
        $directory = $this->path === ":memory:"
            ? sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_sqlite_locks"
            : dirname($this->path);

        return new FileDatabaseLockManager($directory);
    }

    public function resetter(): DatabaseResetterInterface
    {
        return new SqliteDatabaseResetter();
    }
}
