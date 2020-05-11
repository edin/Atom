<?php

declare(strict_types=1);

namespace Atom\Database\Query\Ast;

use Atom\Database\Query\Criteria;

final class Join extends Node
{
    public const LeftJoin  = 1;
    public const RightJoin = 2;
    public const Join = 3;

    public $joinType;
    public $table;
    public $joinCondition;

    public static function create(int $type, string $table, Criteria $joinCondition): Join
    {
        $join = new Join();
        $join->joinType = $type;
        $join->table = Table::fromValue($table);
        $join->joinCondition = $joinCondition;

        return $join;
    }

    public function getJoinType()
    {
        return $this->joinType;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getJoinCondition()
    {
        return $this->joinCondition;
    }
}
