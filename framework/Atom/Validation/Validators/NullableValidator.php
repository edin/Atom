<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidationResult;

class NullableValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}
