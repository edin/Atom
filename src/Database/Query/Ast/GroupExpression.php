<?php

namespace Atom\Database\Query\Ast;

final class GroupExpression
{
    public $node = null;

    public function __construct($node)
    {
        $this->node = $node;
    }

    public function hasExpression(): bool
    {
        return $this->node !== null;
    }
}
