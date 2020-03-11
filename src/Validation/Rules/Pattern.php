<?php

namespace Atom\Validation\Rules;

final class Pattern extends AbstractRule
{
    protected $errorMessage = "requiredError";
    protected $pattern;

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

    public function validateValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
