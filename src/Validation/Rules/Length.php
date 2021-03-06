<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Length extends AbstractRule
{
    protected string $errorMessage = "Length should be between {minValue} and {maxValue} chars";
    protected int $minValue = 0;
    protected int $maxValue = 0;

    public function __construct(int $minValue, int $maxValue)
    {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    public function getAttributes(): array
    {
        return [
            "minValue" => $this->minValue,
            "maxValue" => $this->maxValue,
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

        return ($result >= $this->minValue) && ($result <= $this->maxValue);
    }
}
