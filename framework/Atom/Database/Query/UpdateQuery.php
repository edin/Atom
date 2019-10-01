<?php

namespace Atom\Database\Query;

final class UpdateQuery extends Query
{
    public function table(string $table): self
    {
        return $this->from($table);
    }

    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }
}
