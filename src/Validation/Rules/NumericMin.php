<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class NumericMin extends AbstractRule
{
    protected string $errorMessage = "The field value should be greater or equal to {minValue}";
    protected float $minValue = 0;

    public function __construct(float $minValue)
    {
        $this->minValue = $minValue;
    }

    public function isValid($value): bool
    {
        return ($value >= $this->minValue);
    }
}
