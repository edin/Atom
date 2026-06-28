<?php

declare(strict_types=1);

namespace Atom\Database\Sql\Compiler;

use RuntimeException;

final class PostgresCompiler extends AbstractSqlCompiler
{
    protected function quoteIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function compileLimit(?int $limit, ?int $offset): string
    {
        if ($limit !== null && $offset !== null) {
            return " LIMIT {$limit} OFFSET {$offset}";
        }

        if ($limit !== null) {
            return " LIMIT {$limit}";
        }

        if ($offset !== null) {
            return " OFFSET {$offset}";
        }

        return "";
    }

    protected function compileDeleteLimit(?int $limit): string
    {
        if ($limit !== null) {
            throw new RuntimeException("PostgreSQL does not support DELETE LIMIT.");
        }

        return "";
    }
}
