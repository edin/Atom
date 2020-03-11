<?php

namespace Atom\Validation\Rules;

final class Numeric extends AbstractRule
{
    protected $errorMessage = "urlError";

    public function validateValue($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        return null;
    }
}
