<?php

namespace Atom\Validation\Rules;

final class MaxValue extends AbstractRule
{
    protected $errorMessage = "maxValueError";
    protected $maxValue = 0;

    public function __construct(float $maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public function isValid($value): bool
    {
        return true;
    }
}
