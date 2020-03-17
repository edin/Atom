<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Table;
use Closure;

final class UpdateQuery extends Query
{
    use QueryTrait;

    public function table(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }

    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    public function setValue($field, $value): self
    {
        $this->values[$field] = $value;
        return $this;
    }
}
