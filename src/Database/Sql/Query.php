<?php

declare(strict_types=1);

namespace Atom\Database\Sql;

final class Query
{
    public static function select(string $table): SelectQuery
    {
        return (new SelectQuery())->from($table);
    }

    public static function insert(string $table): InsertQuery
    {
        return (new InsertQuery())->into($table);
    }

    public static function update(string $table): UpdateQuery
    {
        return (new UpdateQuery())->table($table);
    }

    public static function delete(string $table): DeleteQuery
    {
        return (new DeleteQuery())->from($table);
    }
}
