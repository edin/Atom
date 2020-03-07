<?php

namespace Atom\Validation;

spl_autoload_register(function ($className) {
    include "D:\\uwamp\\www\\Atom\\framework\\{$className}.php";
});

class Phone
{
    public $Phone;

    public function getValidation(): Validation
    {
        return Validation::create(function (ValidationBuilder $rule) {
            $rule->Phone->required();
        });
    }
}

class Customer
{
    public $FirstName;
    public $LastName;
    public $Email;
    public $Phones;
    public $Address;

    public function getValidation(): Validation
    {
        return Validation::create(function (ValidationBuilder $rule) {
            $rule->FirstName->required();
            $rule->LastName->required();
            $rule->Email->email()->nullable();
            $rule->Phones->asArray(Phone::class);
        });
    }

    public function validate()
    {
        return $this->getValidation()->validate($this);
    }
}


print_r($result);
