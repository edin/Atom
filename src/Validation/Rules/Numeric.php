<?php

namespace Atom\Validation\Rules;

final class Numeric extends AbstractRule
{
    protected $errorMessage = "urlError";

    public function isValid($value): bool
    {
        if (is_numeric($value)) {
            return $value;
        }
        return null;
    }
}
