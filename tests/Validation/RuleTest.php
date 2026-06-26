<?php

declare(strict_types=1);

namespace Atom\Tests\Validation;

use Atom\Validation\Rules\Between;
use Atom\Validation\Rules\Boolean;
use Atom\Validation\Rules\Date;
use Atom\Validation\Rules\Email;
use Atom\Validation\Rules\In;
use Atom\Validation\Rules\Ip;
use Atom\Validation\Rules\Length;
use Atom\Validation\Rules\Max;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Min;
use Atom\Validation\Rules\MinLength;
use Atom\Validation\Rules\Numeric;
use Atom\Validation\Rules\Pattern;
use Atom\Validation\Rules\Required;
use Atom\Validation\Rules\Url;
use Atom\Validation\ValidationContext;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    private ValidationContext $context;

    protected function setUp(): void
    {
        $this->context = new ValidationContext("field", []);
    }

    private function assertRulePasses(object $rule, mixed $value): void
    {
        $this->assertNull($rule->validate($value, $this->context));
    }

    private function assertRuleFails(object $rule, mixed $value, string $code): void
    {
        $error = $rule->validate($value, $this->context);

        $this->assertNotNull($error);
        $this->assertSame($code, $error->code);
    }

    public function testRequiredRule(): void
    {
        $rule = new Required();

        $this->assertRulePasses($rule, "0");
        $this->assertRulePasses($rule, 0);
        $this->assertRulePasses($rule, false);
        $this->assertRuleFails($rule, "", "required");
        $this->assertRuleFails($rule, "   ", "required");
        $this->assertRuleFails($rule, null, "required");
        $this->assertRuleFails($rule, [], "required");
    }

    public function testStringRules(): void
    {
        $this->assertRulePasses(new MinLength(3), "abc");
        $this->assertRuleFails(new MinLength(3), "ab", "min_length");

        $this->assertRulePasses(new MaxLength(3), "abc");
        $this->assertRuleFails(new MaxLength(3), "abcd", "max_length");

        $this->assertRulePasses(new Length(2, 4), "abcd");
        $this->assertRuleFails(new Length(2, 4), "abcde", "length");
    }

    public function testFormatRules(): void
    {
        $this->assertRulePasses(new Email(), "edin@example.com");
        $this->assertRuleFails(new Email(), "edin", "email");

        $this->assertRulePasses(new Url(), "https://example.com");
        $this->assertRuleFails(new Url(), "example.com", "url");

        $this->assertRulePasses(new Ip(), "127.0.0.1");
        $this->assertRuleFails(new Ip(), "300.0.0.1", "ip");

        $this->assertRulePasses(new Pattern("/^AT-/"), "AT-123");
        $this->assertRuleFails(new Pattern("/^AT-/"), "BT-123", "pattern");
    }

    public function testNumericRules(): void
    {
        $this->assertRulePasses(new Numeric(), "10.5");
        $this->assertRuleFails(new Numeric(), "abc", "numeric");

        $this->assertRulePasses(new Min(5), 5);
        $this->assertRuleFails(new Min(5), 4, "min");

        $this->assertRulePasses(new Max(5), 5);
        $this->assertRuleFails(new Max(5), 6, "max");

        $this->assertRulePasses(new Between(2, 5), 3);
        $this->assertRuleFails(new Between(2, 5), 6, "between");
    }

    public function testOtherRules(): void
    {
        $this->assertRulePasses(new Boolean(), "false");
        $this->assertRuleFails(new Boolean(), "maybe", "boolean");

        $this->assertRulePasses(new In(["a", "b"]), "a");
        $this->assertRuleFails(new In(["a", "b"]), "c", "in");

        $this->assertRulePasses(new Date("Y-m-d"), "2026-06-25");
        $this->assertRuleFails(new Date("Y-m-d"), "25.06.2026", "date");
    }
}

