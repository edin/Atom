<?php

declare(strict_types=1);

namespace Atom\Database\Query\Ast;

final class SortOrder extends Node
{
    public const ASC = "ASC";
    public const DESC = "DESC";
    public const NULLS_FIRST = "NULLS FIRST";
    public const NULLS_LAST  = "NULLS LAST";

    public ?Node $expression = null;
    public string $order = self::ASC;
    public ?string $nullsOrder = null;

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
