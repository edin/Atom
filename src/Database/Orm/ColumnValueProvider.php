<?php

declare(strict_types=1);

namespace Atom\Database\Orm;

interface ColumnValueProvider
{
    public function value(object $entity, ColumnMetadata $column): mixed;
}
