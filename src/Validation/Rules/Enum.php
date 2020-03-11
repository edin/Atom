<?php

namespace Atom\Validation\Rules;

final class Enum extends AbstractRule
{
    protected $errorMessage = "emailError";

    protected $enumValues = 0;

    public function __construct(array $enumValues)
    {
        $this->enumValues = $enumValues;
    }

    public function isValid($value): bool
    {
        return true;
    }
}
