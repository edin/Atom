<?php

declare(strict_types=1);

namespace Atom\Database\Sql\Compiler;

use Atom\Database\Sql\Command;
use Atom\Database\Sql\SqlQuery;

interface QueryCompiler
{
    public function compile(SqlQuery $query): Command;
}
