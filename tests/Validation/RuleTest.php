<?php

namespace Atom\Tests\Validation;

use Atom\Validation\Rules\Ip;
use Atom\Validation\Rules\Url;
use Atom\Validation\Rules\Date;
use Atom\Validation\Rules\Enum;
use PHPUnit\Framework\TestCase;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\Length;
use Atom\Validation\Rules\Pattern;
use Atom\Validation\Rules\Required;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\NumericMax;
use Atom\Validation\Rules\NumericMin;

final class RuleTest extends TestCase
{
    private function isSuccess($rule, $value): void
    {
        $this->assertTrue($rule->validate($value)->isSuccess());
    }

    private function isFailure($rule, $value): void
    {
        $result = $rule->validate($value);
        $this->assertTrue($result->isFailure());
    }

    public function testDateRule(): void
    {
        $r = new Date();
        $this->isSuccess($r, "2020-03-11T18:51:02.295Z");
        $this->isSuccess($r, "2020-03-11T18:51:02");
        $this->isSuccess($r, "2020-03-11 18:51:02");
        $this->isSuccess($r, "2020-03-11");

        $this->isFailure($r, " 2020-03-11");
        $this->isFailure($r, "2020-20-20");
    }

    public function testEmailRule(): void
    {
        $r = new Email();
        $this->isSuccess($r, "edin.omeragic@gmail.com");
        $this->isFailure($r, "edin.omeragic");
        $this->isFailure($r, "@gmail.com");
    }

    public function testIpRule(): void
    {
        $r = new Ip();
        $this->isSuccess($r, "127.0.0.1");
        $this->isFailure($r, "127.0.0.1.0");
        $this->isFailure($r, "300.0.0.0");
    }

    public function testEnumRule(): void
    {
        $r = new Enum(["a", "b", "c"]);
        $this->isSuccess($r, "a");
        $this->isSuccess($r, "b");
        $this->isSuccess($r, "c");

        $this->isFailure($r, "x");
    }

    public function testLengthRule(): void
    {
        $r = new Length(3, 5);
        $this->isSuccess($r, "abc");
        $this->isSuccess($r, "abcd");
        $this->isSuccess($r, "abcde");

        $this->isFailure($r, "ab");
        $this->isFailure($r, "ababab");
    }

    public function testMinValue(): void
    {
        $r = new NumericMin(5);
        $this->isSuccess($r, 7);
        $this->isSuccess($r, 6);
        $this->isSuccess($r, 5);
        $this->isFailure($r, 4);
        $this->isFailure($r, 3);
    }

    public function testMaxValue(): void
    {
        $r = new NumericMax(5);
        $this->isSuccess($r, 4);
        $this->isSuccess($r, 5);
        $this->isFailure($r, 6);
        $this->isFailure($r, 7);
    }

    public function testMinLength(): void
    {
        $r = new MinLength(5);
        $this->isFailure($r, "1234");
        $this->isSuccess($r, "12345");
        $this->isSuccess($r, "123456");
    }

    public function testMaxLength(): void
    {
        $r = new MaxLength(5);
        $this->isSuccess($r, "1234");
        $this->isSuccess($r, "12345");
        $this->isFailure($r, "123456");
    }

    public function testPattern(): void
    {
        $r = new Pattern("/\\d+/");
        $this->isSuccess($r, "100");
    }

    public function testRequired(): void
    {
        $r = new Required();
        $this->isSuccess($r, "100");
        $this->isFailure($r, "");
        $this->isFailure($r, false);
        $this->isFailure($r, null);
        $this->isFailure($r, 0);
    }

    public function testUrl(): void
    {
        $r = new Url();
        $this->isSuccess($r, "http://google.com/");
        $this->isSuccess($r, "https://google.com/");
        $this->isFailure($r, "google.com");
        $this->isFailure($r, "smugoogle");
    }
}
