<?php

namespace Atom\Validation\Rules;

final class Length extends AbstractRule
{
    protected $errorMessage = "lengthError";
    protected $minValue = 0;
    protected $maxValue = 0;

    public function __construct(int $minValue, int $maxValue)
    {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    public function getAttributes(): array
    {
        return [
            "min" => $this->minValue,
            "max" => $this->maxValue,
        ];
    }

    public function isValid($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $result = (int)$value;
        $this->setResultValue($result);
        return ($result >= $this->minValue) && ($result <= $this->maxValue);
    }
}
