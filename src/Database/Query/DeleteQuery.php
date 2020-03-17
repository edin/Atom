<?php

namespace Atom\Database\Query;

use Atom\Database\Query\Ast\Table;

final class DeleteQuery extends Query
{
    use QueryTrait;

    public function from(string $table): self
    {
        $this->table = Table::fromValue($table);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
}
