<?php

namespace Atom\Database\Query\Ast;

final class SortOrder
{
    public const ASC = "ASC";
    public const DESC = "DESC";
    public const NULLS_FIRST = "NULLS FIRST";
    public const NULLS_LAST  = "NULLS LAST";

    public $expression = null;
    public $order = self::ASC;
    public $nullsOrder = null;

    public static function fromColumn(string $value, string $order = self::ASC): self
    {
        $t = new static();
        $t->expression = Column::fromValue($value);
        $t->order = $order;
        return $t;
    }

    public function desc(): self
    {
        $this->order = self::DESC;
        return $this;
    }

    public function nullsFirst(): self
    {
        $this->nullsOrder = self::NULLS_FIRST;
        return $this;
    }

    public function nullsLast(): self
    {
        $this->nullsOrder = self::NULLS_LAST;
        return $this;
    }
}
