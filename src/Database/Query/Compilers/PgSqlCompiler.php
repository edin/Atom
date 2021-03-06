<?php

declare(strict_types=1);

namespace Atom\Database\Query\Compilers;

class PgSqlCompiler extends AbstractCompiler
{
    public function quoteTableName(string $name): string
    {
        return "\"$name\"";
    }

    public function quoteColumnName(string $name): string
    {
        return "\"$name\"";
    }
}
