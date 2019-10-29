<?php

namespace Atom\Validation;

class Customer
{
    public $FirstName;
    public $LastName;
    public $Email;
    public $Phones;
    public $Address;
}

$validation = Validation::create(function (RuleBuilder $rule) {
    $rule->FirstName->required();
    $rule->LastName->required();
    $rule->Email->email()->nullable();

    $rule->Phones->asArray(function (RuleBuilder $rule) {
    });
    $rule->Address->asObject(function (RuleBuilder $rule) {
    });
});

$result = $validation->validate(new Customer());

print_r($result);
