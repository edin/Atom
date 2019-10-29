<?php

namespace Atom\Validation;

interface IValidator
{
    public function setErrorMessage(string $errorMessage): void;
    public function validate($value): ValidationResult;
}
