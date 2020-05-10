<?php

namespace Atom\Validation\Rules;

final class Pattern extends AbstractRule
{
    protected $errorMessage = "The field does not match to specified pattern";
    private $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function getAttributes(): array
    {
        return [
            "pattern" => $this->pattern
        ];
    }

    public function isValid($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_scalar(($value))) {
            $value = (string)$value;
        }

        return preg_match($this->pattern, $value) === 1;
    }
}
