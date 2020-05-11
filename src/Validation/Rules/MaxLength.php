<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class MaxLength extends AbstractRule
{
    protected $errorMessage = "Length of the field should be maximum {maxValue} chars";
    protected $maxValue = 0;

    public function __construct(float $maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public function getAttributes(): array
    {
        return [
            "maxValue" => $this->maxValue
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

        return ($result <= $this->maxValue);
    }
}
