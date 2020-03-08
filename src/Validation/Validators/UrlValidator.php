<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class UrlValidator extends AbstractValidator
{
    protected $errorMessage = "urlError";

    public function getAttributes($value)
    {
        return [
            "value" => $value
        ];
    }

    public function validate($value): ValidatorResult
    {
        $result = $value;
        if ($this->hasValue($result)) {
            $result = filter_var($value, FILTER_VALIDATE_URL);
            if (!$result) {
                return ValidatorResult::failure($this->getErrorMessage(), $this->getAttributes($value));
            }
        }
        return ValidatorResult::success($result);
    }
}
