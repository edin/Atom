<?php

namespace Atom\Validation\Rules;

final class Ip extends AbstractRule
{
    protected $errorMessage = "Value is not a valid IP address";

    public function isValid($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_IP);
        $this->setResultValue($result);
        return $result !== false;
    }
}