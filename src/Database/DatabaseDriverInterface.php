<?php

declare(strict_types=1);

namespace Atom\Database;

use Atom\Database\Migration\Driver\MigrationRepositoryDriverInterface;
use Atom\Database\Lock\DatabaseLockManagerInterface;
use Atom\Database\Schema\Compiler\SchemaCompilerInterface;
use Atom\Database\Schema\Inspector\SchemaInspectorInterface;
use Atom\Database\Schema\Reset\DatabaseResetterInterface;
use Atom\Database\Sql\Compiler\QueryCompilerInterface;
use PDO;

interface DatabaseDriverInterface
{
    public function connect(): PDO;

    public function compiler(): QueryCompilerInterface;

    public function schemaCompiler(): SchemaCompilerInterface;

    public function schemaInspector(DatabaseConnection $connection): SchemaInspectorInterface;

    public function migrationRepositoryDriver(): MigrationRepositoryDriverInterface;

    public function lockManager(DatabaseConnection $connection): DatabaseLockManagerInterface;

    public function resetter(): DatabaseResetterInterface;
}
