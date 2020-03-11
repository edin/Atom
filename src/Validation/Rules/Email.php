<?php

namespace Atom\Validation\Rules;

final class Email extends AbstractRule
{
    protected $errorMessage = "emailError";

    public function isValid($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_EMAIL);
        $this->setResultValue($result);
        return $result !== false;
    }
}
