<?php

namespace Atom\Validation\Validators;

use Atom\Validation\ValidationResult;

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
            "value" => $value
        ];
    }

    public function validate($value): ValidationResult
    {
        $result = $value;
        if (!$this->hasValue($value)) {
            if (preg_match($this->pattern, $value) !== 1) {
                return ValidationResult::failure($this->getErrorMessage(), $this->getAttributes($value));
            }
        }
        return ValidationResult::success($result);
    }
}
