<?php

namespace Atom\Validation;

interface ILocalisation
{
    public function translate(string $message, array $parameters): string;
}
