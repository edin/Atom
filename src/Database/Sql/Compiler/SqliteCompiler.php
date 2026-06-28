<?php

declare(strict_types=1);

namespace Atom\Database\Sql\Compiler;

final class SqliteCompiler extends AbstractSqlCompiler
{
    protected function quoteIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
