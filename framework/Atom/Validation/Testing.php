<?php

namespace Atom\Validation;

spl_autoload_register(function ($className) {
    include "D:\\Devserver\\www\\Atom\\framework\\{$className}.php";
});

class Customer
{
    public $FirstName;
    public $LastName;
    public $Email;
    public $Phones;
    public $Address;
}

$validation = Validation::create(function (ValidationBuilder $rule) {
    $rule->FirstName->required();
    $rule->LastName->required();
    $rule->Email->email()->nullable();

    $rule->Phones->asArray(function (ValidationBuilder $rule) {
    });
    $rule->Address->asObject(function (ValidationBuilder $rule) {
    });
});

$result = $validation->validate(new Customer());

print_r($result);
