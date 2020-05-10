<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\BinaryExpression;
use Atom\Database\Query\Ast\Column;
use Atom\Database\Query\Ast\GroupExpression;

final class Criteria
{
    private $params = [];
    private $expression = null;


    public static function parseWhere(string $where, $value)
    {
        //Following cases are supported:
        //1. id           => field
        //2. u.id         => field with table reference
        //3. u.id OP :p   => field operator and parameter name
        //4. u.id OP x.id => field operator and field

        $parts = explode(" ", trim($where));
        $left = Field::from($parts[0]);

        if (count($parts) == 3) {
            $op = $parts[1];
            $right = $parts[2];
            if ($right[0] === ':') {
                $right = Parameter::from($right, $value);
            } else {
                $right = Field::from($right);
            }
            return [$left, $op, $right];
        }
        return [$left];
    }

    private function combineExpression(string $operator, $where, $value): self
    {
        $result = self::parseWhere($where, $value);

        $exp = new BinaryExpression();

        if (count($result) == 3) {
            [$left, $op, $right] = $result;
            $exp->leftNode = $left;
            $exp->rightNode = $right;
            $exp->operator = $op;
        } else {
            [$left] = $result;
            $epression = Operator::fromValue($value);
            $exp->leftNode = $left;
            $exp->rightNode = $epression;
            $exp->operator = $epression->getOperator();
        }

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

    public function hasExpression(): bool
    {
        return $this->expression !== null;
    }

    public function where(string $where, $value = null): self
    {
        return $this->combineExpression("AND", $where, $value);
    }

    public function orWhere(string $where, $value = null): self
    {
        return $this->combineExpression("OR", $where, $value);
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

    public function addParameters(array $params): self
    {
        foreach ($params as $key => $value) {
            $this->params[$key] = $value;
        }
        return $this;
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

    public function combine(string $operator, Criteria $criteria, bool $group = false)
    {
        $this->addParameters($criteria->getParameters());

        if ($this->expression == null) {
            $this->expression = $criteria->expression;
            return;
        }

        if ($criteria->expression == null) {
            return;
        }

        $expression = new BinaryExpression();
        $expression->operator = $operator;
        $expression->leftNode = $this->expression;
        $expression->rightNode = $criteria->expression;

        if ($group) {
            $expression->leftNode = new GroupExpression($expression->leftNode);
            $expression->rightNode = new GroupExpression($expression->rightNode);
        }

        $this->expression = $expression;
    }
}
