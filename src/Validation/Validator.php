<?php

declare(strict_types=1);

namespace Atom\Validation;

use Atom\Validation\IRule;
use Atom\Validation\Rules\Boolean;
use Atom\Validation\Rules\Date;
use Atom\Validation\Rules\Length;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\NumericMax;
use Atom\Validation\Rules\NumericMin;
use Atom\Validation\Rules\Pattern;
use Atom\Validation\Rules\Required;
use Atom\Validation\Rules\Trim;
use Closure;

class Validator
{
    private const ValidateScalar = 0;
    private const ValidateArray = 1;
    private const ValidateObject = 2;

    private array  $rules = [];
    private IRule  $lastAdded;
    private string $property;
    private int    $validatorType = self::ValidateScalar;
    /**
     *  @var Validation
     */
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

    public function boolean(): self
    {
        return $this->addRule(new Boolean());
    }

    public function trim(): self
    {
        return $this->addRule(new Trim());
    }

    public function pattern(string $pattern): self
    {
        return $this->addRule(new Pattern($pattern));
    }

    public function date(): self
    {
        return $this->addRule(new Date());
    }

    public function asArray(?Closure $builder = null): self
    {
        $this->validatorType = self::ValidateArray;
        if ($builder) {
            $this->validator = Validation::create($builder);
        }
        return $this;
    }

    public function asObject(Closure $builder): self
    {
        $this->validatorType = self::ValidateObject;
        $this->validator = Validation::create($builder);
        return $this;
    }

    public function withErrorMessage(string $errorMessage): self
    {
        $this->getLastRule()->setErrorMessage($errorMessage);
        return $this;
    }

    public function validate($value): ValidationResult
    {
        if ($this->validatorType == self::ValidateArray) {
            $value = is_array($value) ? $value : [];
            return $this->validateArray($value);
        } elseif ($this->validatorType == self::ValidateObject) {
            return $this->validateObject($value);
        }
        return $this->validateScalar($value);
    }

    private function validateScalar($value): ValidationResult
    {
        $result = new ValidationResult;

        $ruleResults = [];
        foreach ($this->rules as $rule) {
            $ruleResults[] = $ruleResult = $rule->validate($value);
            $value = $ruleResult->getValue();
        }

        foreach ($ruleResults as $ruleResult) {
            if ($ruleResult->isFailure()) {
                $errorMessage = new ErrorMessage($ruleResult->getErrorMessage(), $ruleResult->getAttributes());
                $result->addError($errorMessage);
            }
            if ($ruleResult->isSuccess()) {
                $result->setValue($ruleResult->getValue());
            }
        }

        return $result;
    }

    private function validateArray($values): ValidationResult
    {
        $result = new ValidationResult;
        $result->setArrayResults();

        foreach ($values as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $validationResult = $this->validator->validate($value);
            } else {
                $validationResult = $this->validateScalar($value);
            }

            if ($validationResult->hasAnyErrors()) {
                $validationResult->setIndex($key);
                $result->addValidationResult((string) $key, $validationResult);
            }
        }
        return $result;
    }

    private function validateObject($entity): ValidationResult
    {
        return $this->validator->validate($entity);
    }
}
