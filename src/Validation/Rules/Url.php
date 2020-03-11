<?php

namespace Atom\Validation\Rules;

final class Url extends AbstractRule
{
    protected $errorMessage = "urlError";

    public function validateValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
