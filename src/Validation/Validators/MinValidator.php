<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class MinValidator extends AbstractValidator
{
    protected $errorMessage = "minValueError";
    protected $minValue = 0;

    public function __construct(float $minValue)
    {
        $this->minValue = $minValue;
    }

    public function validate($value): ValidatorResult
    {
        $result = $value;
        if ($this->hasValue($result)) {
            $result = (float) $value;
            if ($result < $this->minValue) {
                return ValidatorResult::failure($this->getErrorMessage(), [
                    "value" => $value,
                    "minValue" => $this->minValue,
                ]);
            }
        }
        return ValidatorResult::success($result);
    }
}
