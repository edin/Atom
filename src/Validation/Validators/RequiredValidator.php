<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidationResult;

final class RequiredValidator extends AbstractValidator
{
    protected $errorMessage = "requiredError";

    public function validate($value): ValidationResult
    {
        $result = $value;

        if (!$this->hasValue($value)) {
            return ValidationResult::failure($this->getErrorMessage(), [
                "value" => $value,
            ]);
        }

        return ValidationResult::success($result);
    }
}
