<?php

namespace Atom\Validation;

use Atom\Validation\IValidator;
use Atom\Validation\Validators\EmailValidator;
use Atom\Validation\Validators\MaxValidator;
use Atom\Validation\Validators\MinValidator;
use Atom\Validation\Validators\RequiredValidator;

class ValidationGroup
{
    private $validators = [];
    private $lastAdded;
    private $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function addRule(IValidator $validator): self
    {
        $this->lastAdded = $validator;
        $this->validators[] = $validator;
        return $this;
    }

    public function getLastValidator(): IValidator
    {
        return $this->lastAdded;
    }

    public function required(): self
    {
        return $this->addRule(new RequiredValidator());
    }

    // public function nullable(): self
    // {
    //     return $this->addRule(new NullableValidator());
    // }

    public function min($min): self
    {
        return $this->addRule(new MinValidator($min));
    }

    public function max($max): self
    {
        return $this->addRule(new MaxValidator($max));
    }

    public function email(): self
    {
        return $this->addRule(new EmailValidator());
    }

    public function asArray(callable $callable): self
    {
        return $this;
    }

    public function asObject(callable $callable): self
    {
        return $this;
    }

    public function withErrorMessage(string $errorMessage): self
    {
        $this->getLastValidator()->setErrorMessage($errorMessage);
        return $this;
    }

    public function validate($value)
    {
        $result = [];
        foreach ($this->validators as $validator) {
            $result[] = $validator->validate($value);
        }
        return $result;
    }
}
