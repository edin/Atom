<?php

declare(strict_types=1);

namespace Atom\Database\Driver;

use Atom\Database\Migration\Driver\MySqlMigrationRepositoryDriver;
use Atom\Database\Migration\Driver\MigrationRepositoryDriverInterface;
use Atom\Database\DatabaseConnection;
use Atom\Database\Lock\DatabaseLockManagerInterface;
use Atom\Database\Lock\MySqlDatabaseLockManager;
use Atom\Database\Schema\Compiler\MySqlSchemaCompiler;
use Atom\Database\Schema\Compiler\SchemaCompilerInterface;
use Atom\Database\Schema\Inspector\MySqlSchemaInspector;
use Atom\Database\Schema\Inspector\SchemaInspectorInterface;
use Atom\Database\Schema\Reset\DatabaseResetterInterface;
use Atom\Database\Schema\Reset\MySqlDatabaseResetter;
use Atom\Database\Sql\Compiler\MySqlCompiler;
use Atom\Database\Sql\Compiler\QueryCompiler;

final class MySqlDriver extends AbstractPdoDriver
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
        string $charset = "utf8mb4",
        array $options = []
    ) {
        $dsn = "mysql:dbname={$database};host={$host};charset={$charset}";
        if ($port !== null) {
            $dsn .= ";port={$port}";
        }

        parent::__construct($dsn, $username, $password, $options);
    }

    public function compiler(): QueryCompiler
    {
        return new MySqlCompiler();
    }

    public function schemaCompiler(): SchemaCompilerInterface
    {
        return new MySqlSchemaCompiler();
    }

    public function schemaInspector(DatabaseConnection $connection): SchemaInspectorInterface
    {
        return new MySqlSchemaInspector($connection);
    }

    public function migrationRepositoryDriver(): MigrationRepositoryDriverInterface
    {
        return new MySqlMigrationRepositoryDriver();
    }

    public function lockManager(DatabaseConnection $connection): DatabaseLockManagerInterface
    {
        return new MySqlDatabaseLockManager($connection);
    }

    public function resetter(): DatabaseResetterInterface
    {
        return new MySqlDatabaseResetter();
    }
}
