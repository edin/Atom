<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidationResult;

class MaxValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
