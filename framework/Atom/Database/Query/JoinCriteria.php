<?php

namespace Atom\Database\Query;

class JoinCriteria
{
    private $expression = null;

    private function combineExpression($operator, $column, $value, $isValue)
    {
        //TODO: Combine expressions
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
