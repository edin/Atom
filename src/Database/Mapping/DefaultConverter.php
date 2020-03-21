<?php

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
