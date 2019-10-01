<?php

namespace Atom\Database\Query;

final class InsertQuery extends Query
{
    public function into(string $table): self
    {
        return $this->from($table);
    }

    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }
}
