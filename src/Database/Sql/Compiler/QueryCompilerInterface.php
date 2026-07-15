<?php

declare(strict_types=1);

namespace Atom\Database\Sql\Compiler;

use Atom\Database\Sql\Command;
use Atom\Database\Sql\SqlQueryInterface;

interface QueryCompilerInterface
{
    public function compile(SqlQueryInterface $query): Command;
}
