<?php

declare(strict_types=1);

namespace Atom\Database\Mapping;

use Atom\Database\Interfaces\ITypeConverter;

class DefaultConverter implements ITypeConverter
{
    public function convertTo($value)
    {
        return $value;
    }
    public function convertBack($value)
    {
        return $value;
    }
}
