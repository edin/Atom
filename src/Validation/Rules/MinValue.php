<?php

namespace Atom\Validation\Rules;

final class MinValue extends AbstractRule
{
    protected $errorMessage = "minValueError";
    protected $minValue = 0;

    public function __construct(float $minValue)
    {
        $this->minValue = $minValue;
    }

    public function validatedValue($value)
    {
        //Can't implement with this abstraction
    }
}
