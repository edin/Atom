<?php

namespace Atom\Database;

final class SelectQuery extends Query
{
    private $columns = [];

    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }
}
