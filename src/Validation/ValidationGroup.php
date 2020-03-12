<?php

namespace Atom\Validation;

use Atom\Validation\IRule;
use Atom\Validation\Rules\Length;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\NumericMax;
use Atom\Validation\Rules\NumericMin;
use Atom\Validation\Rules\Required;

class ValidationGroup
{
    private const ValidateScalar = 0;
    private const ValidateArray = 1;
    private const ValidateObject = 2;

    private $rules = [];
    private $lastAdded;
    private $property;
    private $validatorType = self::ValidateScalar;
    private $validator = null;

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

    public function minLength(int $min): self
    {
        return $this->addRule(new MinLength($min));
    }

    public function maxLength(int $max): self
    {
        return $this->addRule(new MaxLength($max));
    }

    public function length(int $min, int $max): self
    {
        return $this->addRule(new Length($min, $max));
    }

    public function min($min): self
    {
        return $this->addRule(new NumericMin($min));
    }

    public function max($max): self
    {
        return $this->addRule(new NumericMax($max));
    }

    public function email(): self
    {
        return $this->addRule(new Email());
    }

    public function asArray(callable $callable): self
    {
        $this->validatorType = self::ValidateArray;
        $this->validator = Validation::create($callable);
        return $this;
    }

    public function asObject(callable $callable): self
    {
        $this->validatorType = self::ValidateObject;
        $this->validator = Validation::create($callable);
        return $this;
    }

    public function withErrorMessage(string $errorMessage): self
    {
        $this->getLastRule()->setErrorMessage($errorMessage);
        return $this;
    }

    public function validate($value): array
    {
        if ($this->validatorType == self::ValidateScalar) {
        } else if ($this->validatorType == self::ValidateArray) {
        } else if ($this->validatorType == self::ValidateObject) {
        }

        $result = new ValidationResult;
        $result = [];
        foreach ($this->validators as $validator) {
            $result[] = $validator->validate($value);
        }
        return $result;
    }
}
