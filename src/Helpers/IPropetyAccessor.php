<?php

namespace Atom\Helpers;

interface IPropetyAccessor
{
    public function getProperty(string $name);
    public function setProperty(string $name, $value);
}
