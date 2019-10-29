<?php

namespace Atom\Validation\Validators;

class MaxValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
