<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Boolean extends AbstractRule
{
    protected $errorMessage = "The field value is not valid";

    public function isValid($value): bool
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value !== null) {
            $this->setResultValue($value);
            return true;
        }
        return false;
    }
}
