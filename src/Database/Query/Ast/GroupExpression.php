<?php

declare(strict_types=1);

namespace Atom\Database\Query\Ast;

final class GroupExpression extends Node
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
