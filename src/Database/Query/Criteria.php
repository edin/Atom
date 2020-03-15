<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\GroupExpression;

final class Criteria
{
    private $params = [];
    private $expression = null;

    private function combineExpression($operator, $column, $value, $isValue): self
    {
        $valueExpression = Operator::fromValue($value);

        $exp = new BinaryExpression();
        $exp->leftNode = Column::fromValue($column);
        $exp->rightNode = $valueExpression;
        $exp->operator = $valueExpression->getOperator();

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

    private function combineGroupExpression($operator, callable $callable)
    {
        $criteria = new static();
        $callable($criteria);

        $expression = $criteria->getExpression();
        $params = $criteria->getParameters();

        if ($expression !== null) {
            $expression = new GroupExpression($expression);

            foreach ($params as $key => $value) {
                $this->addParameter($key, $value);
            }

            if ($this->expression === null) {
                $this->expression = $expression;
            } else {
                $orExpression = new BinaryExpression();
                $orExpression->operator = $operator;
                $orExpression->leftNode = $this->expression;
                $orExpression->rightNode = $expression;
                $this->expression = $orExpression;
            }
        }
        return $this;
    }

    public function orGroup(callable $callable): self
    {
        return $this->combineGroupExpression("OR", $callable);
    }

    public function group(callable $callable): self
    {
        return $this->combineGroupExpression("AND", $callable);
    }

    public function addParameter(string $name, $value): self
    {
        $this->params[$name] = $value;
        return $this;
    }

    public function getParameters(): array
    {
        return $this->params;
    }

    public function getExpression()
    {
        return $this->expression;
    }
}
