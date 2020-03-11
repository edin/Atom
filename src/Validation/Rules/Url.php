<?php

namespace Atom\Validation\Rules;

final class Url extends AbstractRule
{
    protected $errorMessage = "urlError";

    public function isValid($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_URL);
        $this->setResultValue($result);
        return $result !== false;
    }
}
