<?php

namespace Atom\Validation\Validators;

abstract class AbstractValidator implements IValidator
{
    protected $errorMessage;

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
}
