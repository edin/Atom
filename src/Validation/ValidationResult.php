<?php

declare(strict_types=1);

namespace Atom\Validation;

use Countable;
use JsonSerializable;

final class ValidationResult implements Countable, JsonSerializable
{
    private array $modelErrors = [];
    private array $errors = [];
    private $value = null;
    private bool $hasValue = false;
    private $index = null;
    private bool $isArrayResult = false;

    public function addModelError(ErrorMessage $errorMessage)
    {
        $this->modelErrors[] = $errorMessage;
    }

    public function addError(ErrorMessage $errorMessage)
    {
        $this->errors[] = $errorMessage;
    }

    public function addValidationResult(string $fieldName, ValidationResult $validationResult)
    {
        $fieldName = (string) $fieldName;
        $this->errors[$fieldName] = $validationResult;
    }

    public function setArrayResults(): void
    {
        $this->isArrayResult = true;
    }

    public function isArrayResult(): bool
    {
        return $this->isArrayResult;
    }

    public function hasErrorsFor(string $fieldName): bool
    {
        return isset($this->errors[$fieldName]);
    }

    public function getErrorsFor(string $fieldName)
    {
        return $this->errors[$fieldName];
    }

    public function setValue($value): void
    {
        $this->hasValue = true;
        $this->value = $value;
    }

    public function setIndex($value): void
    {
        $this->index = $value;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function hasValue(): bool
    {
        return $this->hasValue;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isValid(): bool
    {
        return count($this->errors) === 0 &&
            count($this->modelErrors) == 0;
    }

    public function hasAnyErrors(): bool
    {
        return !$this->isValid();
    }

    public function hasIndex(): bool
    {
        return $this->index !== null;
    }

    public function jsonSerialize()
    {
        $result = [];
        if (count($this->modelErrors)) {
            $result['modelErrors'] = $this->modelErrors;
        }

        if ($this->hasIndex()) {
            $result['index'] = $this->index;
        }

        if ($this->isArrayResult) {
            $result['errors'] = (object) $this->errors;
        } else {
            $result['errors'] = $this->errors;
        }

        return $result;
    }

    public function count()
    {
        return count($this->errors) + count($this->modelErrors);
    }
}
