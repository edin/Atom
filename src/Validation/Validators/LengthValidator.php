<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class LengthValidator extends AbstractValidator
{
    protected $errorMessage = "lengthError";
    protected $minValue = 0;
    protected $maxValue = 0;

    public function __construct(int $minValue, int $maxValue)
    {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    public function getAttributes($value): array
    {
        return [
            "value"    => $value,
            "minValue" => $this->minValue,
            "maxValue" => $this->maxValue
        ];
    }

    public function validate($value): ValidatorResult
    {
        if ($this->hasValue($value)) {
            $length = strlen($value);

            if ($length < $this->minValue || $length > $this->maxValue) {
                return ValidatorResult::failure($this->getErrorMessage(), $this->getAttributes($value));
            }
        }
        return ValidatorResult::success($value);
    }
}
