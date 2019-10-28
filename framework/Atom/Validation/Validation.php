<?php

interface IValidator
{
    public function setErrorMessage(string $errorMessage): void;
    public function validate($value): ValidationResult;
}

class ValidationResult
{
    private $errorMessage = "";
    private $isValid;

    private function __construct(string $errorMessage, bool $isValid)
    {
        $this->errorMessage = $errorMessage;
        $this->isValid = $isValid;
    }

    public static function success(): self
    {
        return new static("", true);
    }

    public static function failure(string $errorMessage): self
    {
        return new static($errorMessage, false);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function isSuccess(): bool
    {
        return $this->isValid;
    }

    public function isFailure(): bool
    {
        return !$this->isValid;
    }
}

abstract class AbstractValidator implements IValidator
{
    protected $errorMessage;

    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }
}

class MinValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}

class RequiredValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}

class EmailValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}

class NullableValidator extends AbstractValidator
{
    public function validate($value): ValidationResult
    {
        return ValidationResult::failure("Error");
    }
}

class Rule
{
    private $validators = [];

    public function addRule($validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function getLastValidator(): IValidator
    {
        return $this->validators[count($this->validators) - 1];
    }

    public function required(): self
    {
        return $this->addRule(new RequiredValidator());
    }

    public function nullable(): self
    {
        return $this->addRule(new NullableValidator());
    }

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

    public function withErrorMessage(string $errorMessage)
    {
        $this->getLastValidator()->setErrorMessage($errorMessage);
    }
}

class RuleBuilder
{
    public $rules = [];
    public function __get($name)
    {
        if (!isset($this->rules[$name])) {
            $this->rules[$name] = new Rule();
        }
        return $this->rules[$name];
    }
}

class Validation
{
    public static function create(callable $builder)
    {
        $v = new static;
        $v->builder = new RuleBuilder();
        $builder($v->builder);
        return $v;
    }

    public function validate($model)
    {
        print_r($this);
    }
}

class Customer
{
    public $FirstName;
    public $LastName;
    public $Email;
}

$validation = Validation::create(function (RuleBuilder $rule) {
    $rule->FirstName->required();
    $rule->LastName->required();
    $rule->Email->email()->nullable();

    // $rule->Elements->asArray(function(RuleBuilder $rule) {
    // });

    // $rule->Elements->asObject(function(RuleBuilder $rule) {
    // });

    // $rule->Elements->asType(Type::class);
});

$result = $validation->validate(new Customer());

print_r($result);
