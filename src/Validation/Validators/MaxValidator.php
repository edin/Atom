<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidationResult;

final class MaxValidator extends AbstractValidator
{
    protected $errorMessage = "maxValueError";
    protected $maxValue = 0;

    public function __construct(float $maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public function validate($value): ValidationResult
    {
        $result = $value;
        if ($this->hasValue($result)) {
            $result = (float) $value;
            if ($result > $this->maxValue) {
                return ValidationResult::failure($this->getErrorMessage(), [
                    "value" => $value,
                    "maxValue" => $this->maxValue
                ]);
            }
        }
        return ValidationResult::success($result);
    }
}
