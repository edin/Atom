<?php

namespace Atom\Validation;

use Atom\Validation\IRule;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\MaxValue;
use Atom\Validation\Rules\MinValue;
use Atom\Validation\Rules\Required;

class ValidationGroup
{
    private $rules = [];
    private $lastAdded;
    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function addRule(IRule $rule): self
    {
        $this->lastAdded = $rule;
        $this->rules[] = $rule;
        return $this;
    }

    public function getLastRule(): IRule
    {
        return $this->lastAdded;
    }

    public function required(): self
    {
        return $this->addRule(new Required());
    }

    public function min($min): self
    {
        return $this->addRule(new MinValue($min));
    }

    public function max($max): self
    {
        return $this->addRule(new MaxValue($max));
    }

    public function email(): self
    {
        return $this->addRule(new Email());
    }

    public function asArray(callable $callable): self
    {
        //TODO: Add array validator
        return $this;
    }

    public function asObject(callable $callable): self
    {
        //TODO: Add object validator
        return $this;
    }

    public function withErrorMessage(string $errorMessage): self
    {
        $this->getLastRule()->setErrorMessage($errorMessage);
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
