<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\BinaryExpression;

class Criteria
{
    private $params = [];
    private $expression = null;

    private function combineExpression($operator, $column, $value, $isValue): self
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
        return $this;
    }

    public function on(string $column, $value): self
    {
        return $this->combineExpression("AND", $column, $value, false);
    }

    public function orOn(string $column, $value): self
    {
        return $this->combineExpression("OR", $column, $value, false);
    }

    public function where(string $column, $value): self
    {
        return $this->combineExpression("AND", $column, $value, true);
    }

    public function orWhere(string $column, $value): self
    {
        return $this->combineExpression("OR", $column, $value, true);
    }

    public function orGroup(callable $callable): self
    {
        return $this;
    }
}
