<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class EmailValidator extends AbstractValidator
{
    protected $errorMessage = "emailError";

    public function validate($value): ValidatorResult
    {
        $result = $value;
        if ($this->hasValue($result)) {
            $result = filter_var($value, FILTER_VALIDATE_EMAIL);
            if (!$result) {
                return ValidatorResult::failure($this->getErrorMessage(), ['value' => $value]);
            }
        }
        return ValidatorResult::success($result);
    }
}
