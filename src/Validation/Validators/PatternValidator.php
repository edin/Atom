<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidatorResult;

final class PatternValidator extends AbstractValidator
{
    protected $errorMessage = "requiredError";
    protected $pattern = 0;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function getAttributes($value): array
    {
        return [
            "value" => $value,
        ];
    }

    public function validate($value): ValidatorResult
    {
        $result = $value;
        if (!$this->hasValue($value)) {
            if (preg_match($this->pattern, $value) !== 1) {
                return ValidatorResult::failure($this->getErrorMessage(), $this->getAttributes($value));
            }
        }
        return ValidatorResult::success($result);
    }
}
