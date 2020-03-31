<?php

namespace Atom\Helpers;

interface IPropertyAccessor
{
    public function getProperty(string $name);
    public function setProperty(string $name, $value);
}
