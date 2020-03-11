<?php

namespace Atom\Validation\Rules;

class Nullable extends AbstractRule
{
    protected $errorMessage = "nullableError";

    public function isValid($value): bool
    {
        return is_null($value);
    }
}
