<?php

namespace Atom\Tests\Validation;

use Atom\Validation\Validation;
use Atom\Validation\ValidationBuilder;
use PHPUnit\Framework\TestCase;

class Customer
{
    public $firstName;
    public $lastName;
    public $email;
    public $address;
}

final class ValidationTest extends TestCase
{
    public function testValidationBuilder(): void
    {
        $validator = Validation::create(function (ValidationBuilder $rule) {
            $rule->firstName->required()->trim()->length(4, 30);
            $rule->lastName->required()->trim()->length(4, 30);
            $rule->email->required()->email();
            $rule->address->required()->boolean();
        });

        $customer = new Customer;
        $customer->firstName = "Edin";
        $customer->lastName = "Omeragic";
        $customer->email = "edin.omeragic@gmail.com";

        $validationResult = $validator->validate($customer);
    }
}
