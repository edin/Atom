<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Email extends AbstractRule
{
    protected $errorMessage = "Value is not valid Email address";

    public function isValid($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_EMAIL);
        $this->setResultValue($result);
        return $result !== false;
    }
}
