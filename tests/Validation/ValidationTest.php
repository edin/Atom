<?php

namespace Atom\Tests\Validation;

use Atom\Tests\Validation\Types\Address;
use Atom\Tests\Validation\Types\Customer;
use Atom\Validation\Validation;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    private $customer;
    private $validator;

    protected function setUp(): void
    {
        $this->customer = new Customer;
        $this->customer->firstName = "Edin";
        $this->customer->lastName = " Omeragic ";
        $this->customer->email = "edin.omeragic@gmail.com";
        $this->customer->phones = ["", "", "Phone"];

        $this->customer->address = new Address;
        $this->customer->address->city = "";
        $this->customer->address->street = " Street Address ";

        $this->validator = Validation::create(function (Validation $rule) {
            $rule->firstName->required()->trim()->length(8, 30);
            $rule->lastName->required()->trim()->length(14, 30);
            $rule->email->required()->email();
            $rule->address->asObject(function (Validation $rule) {
                $rule->city->trim()->required();
                $rule->street->trim()->required();
            });
            $rule->phones->asArray(function (Validation $rule) {
                $rule->phone->required();
            });
        });
    }

    public function testValidationBuilder(): void
    {
        $result = $this->validator->validate($this->customer);
        $this->assertFalse($result->isValid());
    }

    public function testFilteringValues(): void
    {
        $result = $this->validator->validate($this->customer);
        $this->assertEquals("Omeragic", $this->customer->lastName);
    }

    public function testNestedFilteringValues(): void
    {
        $result = $this->validator->validate($this->customer);
        $this->assertEquals("Street Address", $this->customer->address->street);
    }
}
