<?php

namespace Atom\Validation\Validators;

class EmailValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
