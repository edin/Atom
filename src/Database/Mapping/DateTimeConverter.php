<?php

namespace Atom\Database\Mapping;

use DateTimeImmutable;
use Atom\Database\Interfaces\ITypeConverter;

class DateTimeConverter implements ITypeConverter
{
    public function convertTo($value)
    {
        return DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $value);
    }

    public function convertBack($value)
    {
        return $value->format("Y-m-d H:i:s");
    }
}
