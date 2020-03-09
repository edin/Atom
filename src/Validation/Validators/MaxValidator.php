<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class MaxValidator extends AbstractValidator
{
    protected $errorMessage = "maxValueError";
    protected $maxValue = 0;

    public function __construct(float $maxValue)
    {
        $this->maxValue = $maxValue;
    }

    public function validate($value): ValidatorResult
    {
        $result = $value;
        if ($this->hasValue($result)) {
            $result = (float) $value;
            if ($result > $this->maxValue) {
                return ValidatorResult::failure($this->getErrorMessage(), [
                    "value" => $value,
                    "maxValue" => $this->maxValue,
                ]);
            }
        }
        return ValidatorResult::success($result);
    }
}
