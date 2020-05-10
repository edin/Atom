<?php

namespace Atom\Validation\Rules;

final class Numeric extends AbstractRule
{
    protected $errorMessage = "The field should have numeric value";

    public function isValid($value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value == false) {
            $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        }

        if ($value !== false) {
            $this->setResultValue($value);
            return true;
        }

        return false;
    }
}