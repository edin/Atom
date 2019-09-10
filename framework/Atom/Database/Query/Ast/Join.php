<?php

namespace Atom\Database\Query\Ast;

final class Join
{
    public const LeftJoin  = 1;
    public const RightJoin = 2;
    public const Join = 3;

    public $joinType;
    public $table;
    public $joinCondition;

    public static function create(int $type, string $table, $joinCondition): Join
    {
        $join = new Join();
        $join->joinType = $type;
        $join->table = Table::fromValue($table);
        $join->joinCondition = $joinCondition;
        return $join;
    }
}
