<?php

namespace Atom\Validation\Rules;

final class Required extends AbstractRule
{
    protected $errorMessage = "requiredError";

    public function isValid($value): bool
    {
        if (!empty($value)) {
            return $value;
        }
        return null;
    }
}
