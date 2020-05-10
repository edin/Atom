<?php

namespace Atom\Database\Query\Ast;

final class UnaryExpression extends Node
{
    public $operator = null;
    public $node = null;
}
