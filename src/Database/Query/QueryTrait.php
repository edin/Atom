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

    public function where(Closure $criteriaBuilder): self
    {
        $criteria = new Criteria();
        $criteriaBuilder($criteria);
        $this->where = $criteria;
        return $this;
    }
}
