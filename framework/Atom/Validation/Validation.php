<?php

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

class Rule
{
    public function required(): Rule
    {
        $this->required = true;
        return $this;
    }

    public function nullable(): Rule
    {
        $this->nullable = true;
        return $this;
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


Validation::create(function (RuleBuilder $rule) {
    $rule->FieldName->required();
    $rule->LastName->required();
    $rule->Email->email()->nullable();
});


$validation = new Validation();
$validation->FirstName->required()->unique(function () {
});
$validation->LastName->required();
$validation->Email->required();

$customer = $validation->map(new Customer);

print_r($validation);
