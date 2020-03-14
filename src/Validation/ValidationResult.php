<?php

namespace Atom\Validation;

use JsonSerializable;

final class ValidationResult implements JsonSerializable
{
    private $errorMessages = [];
    private $errors = [];
    private $value = null;
    private $hasValue = false;

    public function addErrorMessage(ErrorMessage $errorMessage) {
        $this->errorMessages[] = $errorMessage;
    }

    public function addError(string $fieldName, ErrorMessage $errorMessage) 
    {
        $this->errors[$fieldName][] = $errorMessage;
    }

    public function addValidationResult(string $fieldName, ValidationResult $validationResult) 
    {
        $this->errors[$fieldName] = $validationResult;
    }

    public function setValue($value): void {
        $this->hasValue = true;
        $this->value = $value;
    }

    public function hasValue(): bool {
        return $this->hasValue;
    }

    public function getValue() {
        return $this->value;
    }

    public function isValid() {
        return count($this->errors) === 0 &&
               count($this->errorMessages) == 0;
    }

    public function hasErrors() {
        return !$this->isValid();
    }

    public function jsonSerialize()
    {
        return [
            'errorMessages' => $this->errorMessages,
            'errors' => $this->errors
        ];
    }
}
