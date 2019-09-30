<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Ast\UnaryExpression;

class JoinCriteria
{
    private $expression = null;

    private function combineExpression($operator, $column, $value, $isValue)
    {
        $exp = new BinaryExpression();
        $exp->leftNode = $column;
        $exp->rightNode = $value;

        if ($this->expression == null) {
            $this->expression = $exp;
        } else {
            $node = new BinaryExpression();
            $node->operator = $operator;
            $node->leftNode = $this->expression;
            $node->rightNode = $exp;
            $this->expression = $node;
        }
    }

    public function on(string $column, $value): self
    {
        $this->combineExpression("AND", $column, $value, false);
        return $this;
    }

    public function orOn(string $column, $value): self
    {
        $this->combineExpression("OR", $column, $value, false);
        return $this;
    }

    public function where(string $column, $value): self
    {
        $this->combineExpression("AND", $column, $value, true);
        return $this;
    }

    public function orWhere(string $column, $value): self
    {
        $this->combineExpression("OR", $column, $value, true);
        return $this;
    }

    public function orGroup(callable $callable): self
    {
        return $this;
    }
}
