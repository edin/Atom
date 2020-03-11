<?php

namespace Atom\Validation\Rules;

class Nullable extends AbstractRule
{
    protected $errorMessage = "nullableError";

    protected function hasValue($value): bool
    {
        return true;
    }

    public function validatedValue($value)
    {
        //Can't implement with this abstraction
    }
}
