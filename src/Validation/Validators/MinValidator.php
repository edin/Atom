<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidationResult;

final class MinValidator extends AbstractValidator
{
    protected $errorMessage = "minValueError";
    protected $minValue = 0;

    public function __construct(float $minValue)
    {
        $this->minValue = $minValue;
    }

    public function validate($value): ValidationResult
    {
        $result = $value;
        if ($this->hasValue($result)) {
            $result = (float) $value;
            if ($result < $this->minValue) {
                return ValidationResult::failure($this->getErrorMessage(), [
                    "value" => $value,
                    "minValue" => $this->minValue
                ]);
            }
        }
        return ValidationResult::success($result);
    }
}
