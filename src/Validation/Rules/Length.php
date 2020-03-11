<?php

namespace Atom\Validation\Rules;

final class LengthValidator extends AbstractRule
{
    protected $errorMessage = "lengthError";
    protected $minValue = 0;
    protected $maxValue = 0;

    public function __construct(int $minValue, int $maxValue)
    {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    public function getAttributes($value): array
    {
        return [
            "minValue" => $this->minValue,
            "maxValue" => $this->maxValue,
        ];
    }
}
