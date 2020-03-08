<?php

namespace Atom\Validation\Validators;

use Atom\Validation\IValidator;

abstract class AbstractValidator implements IValidator
{
    protected $errorMessage = "";

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    protected function hasValue($value): bool
    {
        return !empty($value);
    }
}
