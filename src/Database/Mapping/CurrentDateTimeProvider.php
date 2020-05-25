<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use DateTimeImmutable;
use Atom\Database\Interfaces\IValueProvider;

class CurrentDateTimeProvider implements IValueProvider
{
    public function getValue()
    {
        return new DateTimeImmutable();
    }
}
