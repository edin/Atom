<?php

namespace Atom\Validation\Rules;

final class MinLength extends AbstractRule
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
