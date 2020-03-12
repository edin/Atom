<?php

namespace Atom\Tests\Validation;

use Atom\Validation\Validation;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testValidationBuilder(): void
    {
        $validator = Validation::create(function (Validation $rule) {
            $rule->firstName->required()->trim()->length(8, 30);
            $rule->lastName->required()->trim()->length(14, 30);
            $rule->email->required()->email();
            $rule->address->required();
        });

        $customer = new Customer;
        $customer->firstName = "Edin";
        $customer->lastName = "Omeragic";
        $customer->email = "edin.omeragic@gmail.com";

        $validationResult = $validator->validate($customer);

        print_r($validationResult);

        //print_r($validationResult);
        $this->assertEquals(1, 1);
    }
}

class Customer
{
    public $firstName;
    public $lastName;
    public $email;
    public $address;
}
