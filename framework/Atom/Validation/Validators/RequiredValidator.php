<?php

namespace Atom\Validation\Validators;

class RequiredValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
