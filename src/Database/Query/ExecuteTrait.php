<?php

declare(strict_types=1);

namespace Atom\Database\Query;

trait ExecuteTrait
{
    public function execute()
    {
        return $this->getConnection()->compileQuery($this)->execute();
    }
}
