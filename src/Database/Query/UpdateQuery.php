<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Table;

final class UpdateQuery extends Query
{
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
}
