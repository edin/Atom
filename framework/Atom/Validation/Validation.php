<?php

interface IValidator
{
    public function setErrorMessage(string $errorMessage): void;
}

class Rule
{
    private $validators = [];

    public function addValidator($validator): self
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
        return $this->addRule(RequiredValidator());
    }

    public function nullable(): self
    {
        return $this->addRule(NullableValidator());
    }

    public function min($min): self
    {
        return $this->addRule(MinValidator($min));
    }

    public function max($max): self
    {
        return $this->addRule(MaxValidator($max));
    }

    public function email(): self
    {
        return $this->addRule(EmailValidator());
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
}

class Customer
{
    public $FirstName;
    public $LastName;
    public $Email;
}

$validation = Validation::create(function (RuleBuilder $rule) {
    $rule->FieldName->required();
    $rule->LastName->required();
    $rule->Email->email()->nullable();
});

$result = $validation->validate(new Customer());

print_r($result);
