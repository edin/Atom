<?php

namespace Atom\Validation\Validators;

class NullableValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
