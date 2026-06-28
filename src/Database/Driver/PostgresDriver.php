<?php

declare(strict_types=1);

namespace Atom\Database\Driver;

use Atom\Database\DatabaseConnection;
use Atom\Database\Lock\DatabaseLockManagerInterface;
use Atom\Database\Lock\PostgresDatabaseLockManager;
use Atom\Database\Migration\Driver\MigrationRepositoryDriverInterface;
use Atom\Database\Migration\Driver\PostgresMigrationRepositoryDriver;
use Atom\Database\Schema\Compiler\PostgresSchemaCompiler;
use Atom\Database\Schema\Compiler\SchemaCompilerInterface;
use Atom\Database\Schema\Inspector\PostgresSchemaInspector;
use Atom\Database\Schema\Inspector\SchemaInspectorInterface;
use Atom\Database\Schema\Reset\DatabaseResetterInterface;
use Atom\Database\Schema\Reset\PostgresDatabaseResetter;
use Atom\Database\Sql\Compiler\PostgresCompiler;
use Atom\Database\Sql\Compiler\QueryCompiler;

final class PostgresDriver extends AbstractPdoDriver
{
    /**
     * @param array<int, mixed> $options
     */
    public function __construct(
        string $database,
        string $host = "localhost",
        ?string $username = null,
        ?string $password = null,
        ?int $port = null,
        array $options = []
    ) {
        $dsn = "pgsql:dbname={$database};host={$host}";
        if ($port !== null) {
            $dsn .= ";port={$port}";
        }

        parent::__construct($dsn, $username, $password, $options);
    }

    public function compiler(): QueryCompiler
    {
        return new PostgresCompiler();
    }

    public function schemaCompiler(): SchemaCompilerInterface
    {
        return new PostgresSchemaCompiler();
    }

    public function schemaInspector(DatabaseConnection $connection): SchemaInspectorInterface
    {
        return new PostgresSchemaInspector($connection);
    }

    public function migrationRepositoryDriver(): MigrationRepositoryDriverInterface
    {
        return new PostgresMigrationRepositoryDriver();
    }

    public function lockManager(DatabaseConnection $connection): DatabaseLockManagerInterface
    {
        return new PostgresDatabaseLockManager($connection);
    }

    public function resetter(): DatabaseResetterInterface
    {
        return new PostgresDatabaseResetter();
    }
}
