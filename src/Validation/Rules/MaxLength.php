<?php

namespace Atom\Validation\Rules;

final class MaxLength extends AbstractRule
{
    protected $errorMessage = "minValueError";
    protected $minValue = 0;

    public function __construct(float $minValue)
    {
        $this->minValue = $minValue;
    }

    public function isValid($value): bool
    {
        return true;
    }
}
