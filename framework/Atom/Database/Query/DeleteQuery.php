<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Table;

final class DeleteQuery extends Query
{
    public function from(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }
}
