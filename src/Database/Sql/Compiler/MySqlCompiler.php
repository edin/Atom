<?php

declare(strict_types=1);

namespace Atom\Database\Sql\Compiler;

class MySqlCompiler extends AbstractSqlCompiler
{
    protected function quoteIdentifier(string $name): string
    {
        return "`" . str_replace("`", "``", $name) . "`";
    }

    protected function compileLimit(?int $limit, ?int $offset): string
    {
        if ($limit !== null && $offset !== null) {
            return " LIMIT {$offset}, {$limit}";
        }

        if ($limit !== null) {
            return " LIMIT {$limit}";
        }

        return "";
    }

    protected function compileDeleteLimit(?int $limit): string
    {
        return $limit === null ? "" : " LIMIT {$limit}";
    }
}
