<?php

declare(strict_types=1);

namespace Atom\Validation;

class Localisation implements ILocalisation
{
    public function translate(string $message, array $parameters): string
    {
        return $message;
    }
}
