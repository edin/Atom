<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Provider;

use Atom\Database\Orm\ColumnMetadata;
use Atom\Database\Orm\ColumnValueProvider;
use DateTimeImmutable;

final class NowProvider implements ColumnValueProvider
{
    public function value(object $entity, ColumnMetadata $column): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
