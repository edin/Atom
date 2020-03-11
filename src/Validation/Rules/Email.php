<?php

namespace Atom\Validation\Rules;

final class Email extends AbstractRule
{
    protected $errorMessage = "emailError";

    public function validateValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
