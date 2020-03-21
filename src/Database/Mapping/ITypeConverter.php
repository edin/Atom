<?php

interface ITypeConverter
{
    public function convertTo($value);
    public function convertBack($value);
}
