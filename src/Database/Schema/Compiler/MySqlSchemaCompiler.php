<?php

declare(strict_types=1);

namespace Atom\Database\Schema\Compiler;

final class MySqlSchemaCompiler extends AbstractSchemaCompiler
{
    protected function name(string $name): string
    {
        return "`" . str_replace("`", "``", $name) . "`";
    }

    protected function autoIncrement(): string
    {
        return "AUTO_INCREMENT";
    }

    protected function stringType(int $length): string
    {
        return "VARCHAR({$length})";
    }

    protected function textType(): string
    {
        return "TEXT";
    }

    protected function integerType(): string
    {
        return "INT";
    }

    protected function booleanType(): string
    {
        return "TINYINT(1)";
    }

    protected function dateTimeType(): string
    {
        return "DATETIME";
    }

    protected function timestampType(): string
    {
        return "DATETIME";
    }
}

