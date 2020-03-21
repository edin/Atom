<?php

namespace Atom\Database\Query;

use Closure;

trait QueryTrait
{
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    private function combineCriteria(string $operator, ?Criteria $left, ?Criteria $right, bool $group = false)
    {
        if ($left === null) {
            return $right;
        }
        if ($right === null) {
            return $left;
        }
        $left->combine($operator, $right, $group);
        return $left;
    }


    public function where(...$params)
    {
        if (count($params) == 1 && $params[0] instanceof Closure) {
            $this->whereGroup($params[0]);
        } elseif (count($params) == 2) {
            $criteria = new Criteria();
            $criteria->where(...$params);
            $this->where = $this->combineCriteria("AND", $this->where, $criteria);
        }
        return $this;
    }

    public function orWhere(...$params)
    {
        if (count($params) == 1 && $params[0] instanceof Closure) {
            $this->orWhereGroup($params[0]);
        } elseif (count($params) == 2) {
            $criteria = new Criteria();
            $criteria->orWhere(...$params);
            $this->where = $this->combineCriteria("OR", $this->where, $criteria);
        }
        return $this;
    }

    public function whereGroup(Closure $criteriaBuilder): self
    {
        $criteria = new Criteria();
        $criteriaBuilder($criteria);
        $this->where = $this->combineCriteria("AND", $this->where, $criteria, true);
        return $this;
    }

    public function orWhereGroup(Closure $criteriaBuilder): self
    {
        $criteria = new Criteria();
        $criteriaBuilder($criteria);
        $this->where = $this->combineCriteria("OR", $this->where, $criteria, true);
        return $this;
    }
}
