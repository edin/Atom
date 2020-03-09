<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

class NullableValidator extends AbstractValidator
{
    protected $errorMessage = "minValueError";

    public function validate($value): ValidatorResult
    {
        return ValidatorResult::failure("Error");
    }
}
