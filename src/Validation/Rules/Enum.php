<?php

declare(strict_types=1);

namespace Atom\Validation\Rules;

final class Enum extends AbstractRule
{
    protected string $errorMessage = "Value is not one of the given values";

    protected $enumValues = 0;

    public function __construct(array $enumValues)
    {
        $this->enumValues = $enumValues;
    }

    public function isValid($value): bool
    {
        foreach ($this->enumValues as $enumValue) {
            if ($value === $enumValue) {
                return true;
            }
        }
        return false;
    }
}
