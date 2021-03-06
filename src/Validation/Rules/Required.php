<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Required extends AbstractRule
{
    protected string $errorMessage = "The field value is required";

    protected function hasValue($value): bool
    {
        return true;
    }

    public function isValid($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value !== "") {
                return true;
            }
        }

        return false;
    }
}
