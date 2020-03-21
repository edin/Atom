<?php

class JsonConverter implements ITypeConverter
{
    public function convertTo($value)
    {
        return  json_decode($value);
    }

    public function convertBack($value)
    {
        return json_encode($value);
    }
}
