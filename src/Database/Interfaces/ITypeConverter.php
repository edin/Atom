<?php

namespace Atom\Database\Interfaces;

interface ITypeConverter
{
    public function convertTo($value);
    public function convertBack($value);
}
