<?php

declare(strict_types=1);

namespace Atom\Database\Orm\Provider;

use Atom\Database\Orm\ColumnMetadata;
use Atom\Database\Orm\ColumnValueProviderInterface;
use DateTimeImmutable;

final class NowProvider implements ColumnValueProviderInterface
{
    public function value(object $entity, ColumnMetadata $column): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
