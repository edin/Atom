<?php

namespace Atom\Tests\Validation;

use Atom\Validation\Rules\Date;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\Enum;
use Atom\Validation\Rules\Length;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\MaxValue;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\MinValue;
use Atom\Validation\Rules\Nullable;
use Atom\Validation\Rules\Pattern;
use Atom\Validation\Rules\Required;
use Atom\Validation\Rules\Url;
use PHPUnit\Framework\TestCase;

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

    public function testEnumRule(): void
    {
        $r = new Enum(["a", "b", "c"]);
        $this->isSuccess($r, "a");
        $this->isSuccess($r, "b");
        $this->isSuccess($r, "c");

        $this->isFailure($r, "");
        $this->isFailure($r, false);
        $this->isFailure($r, []);
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

    public function testMaxValue(): void
    {
        $r = new MaxValue(5);
        $this->isSuccess($r, 7);
        $this->isSuccess($r, 6);
        $this->isSuccess($r, 5);
        $this->isFailure($r, 4);
        $this->isFailure($r, 3);
    }

    public function testMinValue(): void
    {
        $r = new MinValue(5);
        $this->isSuccess($r, 4);
        $this->isSuccess($r, 5);
        $this->isFailure($r, 6);
        $this->isFailure($r, 7);
    }

    public function testMinLength(): void
    {
        $r = new MinLength(5);
        $this->isSuccess($r, 4);
        $this->isSuccess($r, 5);
        $this->isFailure($r, 6);
        $this->isFailure($r, 7);
    }

    public function testMaxLength(): void
    {
        $r = new MaxLength(5);
        $this->isSuccess($r, 4);
        $this->isSuccess($r, 5);
        $this->isFailure($r, 6);
        $this->isFailure($r, 7);
    }

    public function testNullable(): void
    {
        $r = new Nullable();
        $this->isSuccess($r, 1);
    }

    public function testPattern(): void
    {
        $r = new Pattern("\d+");
        $this->isSuccess($r, "100");
    }

    public function testRequired(): void
    {
        $r = new Required();
    }

    public function testUrl(): void
    {
        $r = new Url();
    }
}
