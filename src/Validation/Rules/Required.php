<?php

namespace Atom\Validation\Rules;

final class Required extends AbstractRule
{
    protected $errorMessage = "requiredError";

    protected function hasValue($value): bool
    {
        return true;
    }

    public function validatedValue($value)
    {
        if (!empty($value)) {
            return $value;
        }
        return null;
    }
}
