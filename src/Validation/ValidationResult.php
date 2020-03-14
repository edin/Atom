<?php

namespace Atom\Validation;

use JsonSerializable;

final class ValidationResult implements JsonSerializable
{
    private $errorMessages = [];
    private $errors = [];
    private $value = null;

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
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }

    public function isValid() {
        return count($this->errors) === 0 &&
               count($this->errorMessages) == 0;
    }

    public function jsonSerialize()
    {
        return [
            'errorMessages' => $this->errorMessages,
            'errors' => $this->errors
        ];
    }
}
