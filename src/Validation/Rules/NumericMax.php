<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class NumericMax extends AbstractRule
{
    protected string $errorMessage = "The field value should be less or equal to {maxValue}";
    protected float $maxValue = 0;

    public function __construct(float $maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public function isValid($value): bool
    {
        return ($value <= $this->maxValue);
    }
}
