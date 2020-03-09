<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class RequiredValidator extends AbstractValidator
{
    protected $errorMessage = "requiredError";

    public function validate($value): ValidatorResult
    {
        $result = $value;

        if (!$this->hasValue($value)) {
            return ValidatorResult::failure($this->getErrorMessage(), [
                "value" => $value,
            ]);
        }

        return ValidatorResult::success($result);
    }
}
