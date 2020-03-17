<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Table;
use Closure;

final class InsertQuery extends Query
{
    use QueryTrait;

    public function into(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }

    public function values(array $values): self
    {
        $this->values = $values;
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
