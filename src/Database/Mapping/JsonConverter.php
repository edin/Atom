<?php

namespace Atom\Database\Mapping;

use Atom\Database\Interfaces\ITypeConverter;

class JsonConverter implements ITypeConverter
{
    public function convertTo($value)
    {
        if (empty($value)) {
            return null;
        }
        return json_decode($value);
    }

    public function convertBack($value)
    {
        if (empty($value)) {
            return null;
        }
        return json_encode($value);
    }
}
