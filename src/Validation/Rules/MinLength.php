<?php

namespace Atom\Validation\Rules;

final class MinLength extends AbstractRule
{
    protected $errorMessage = "Length of the field should be at least {minValue} char(s)";
    protected $minValue = 0;

    public function __construct(int $minValue)
    {
        $this->minValue = $minValue;
    }

    public function getAttributes(): array
    {
        return [
            "minValue" => $this->minValue
        ];
    }

    public function isValid($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (function_exists("mb_strlen")) {
            $result = mb_strlen($value);
        } else {
            $result = strlen($value);
        }

        return ($result >= $this->minValue);
    }
}
