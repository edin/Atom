<?php

declare(strict_types=1);

namespace Atom\Database\Query\Ast;

final class BinaryExpression extends Node
{
    public $operator = null;
    public $leftNode = null;
    public $rightNode = null;
}
