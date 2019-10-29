<?php

namespace Atom\Validation\Validators;

class MinValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
