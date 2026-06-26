<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Parser\Token\ViewTokenType;
use Atom\View\Parser\ViewParseException;
use Atom\View\Parser\ViewTokenizer;
use PHPUnit\Framework\TestCase;

final class ViewTokenizerTest extends TestCase
{
    public function testTokenizesElementsAttributesTextAndComments(): void
    {
        $tokens = (new ViewTokenizer())->tokenize(
            '<div class="card" data-id=42 disabled><!-- ignored -->Hello<input /></div>'
        );

        $this->assertSame(ViewTokenType::StartTag, $tokens[0]->type);
        $this->assertSame("div", $tokens[0]->value);
        $this->assertSame("class", $tokens[0]->attributes[0]->name);
        $this->assertSame("card", $tokens[0]->attributes[0]->value);
        $this->assertSame("data-id", $tokens[0]->attributes[1]->name);
        $this->assertSame("42", $tokens[0]->attributes[1]->value);
        $this->assertTrue($tokens[0]->attributes[2]->value);
        $this->assertFalse($tokens[0]->attributes[0]->bound);

        $this->assertSame(ViewTokenType::Comment, $tokens[1]->type);
        $this->assertSame(" ignored ", $tokens[1]->value);
        $this->assertSame(ViewTokenType::Text, $tokens[2]->type);
        $this->assertSame("Hello", $tokens[2]->value);
        $this->assertSame(ViewTokenType::StartTag, $tokens[3]->type);
        $this->assertSame("input", $tokens[3]->value);
        $this->assertTrue($tokens[3]->selfClosing);
        $this->assertSame(ViewTokenType::EndTag, $tokens[4]->type);
        $this->assertSame("div", $tokens[4]->value);
    }

    public function testTokenizesBoundAttributes(): void
    {
        $tokens = (new ViewTokenizer())->tokenize(
            '<MyButton text="Save" :disabled="$isDisabled" :variant="$style" />'
        );

        $this->assertSame(ViewTokenType::StartTag, $tokens[0]->type);
        $this->assertSame("MyButton", $tokens[0]->value);
        $this->assertSame("text", $tokens[0]->attributes[0]->name);
        $this->assertSame("Save", $tokens[0]->attributes[0]->value);
        $this->assertFalse($tokens[0]->attributes[0]->bound);
        $this->assertSame("disabled", $tokens[0]->attributes[1]->name);
        $this->assertSame('$isDisabled', $tokens[0]->attributes[1]->value);
        $this->assertTrue($tokens[0]->attributes[1]->bound);
        $this->assertSame("variant", $tokens[0]->attributes[2]->name);
        $this->assertSame('$style', $tokens[0]->attributes[2]->value);
        $this->assertTrue($tokens[0]->attributes[2]->bound);
    }

    public function testTokenizesAttributeSpreads(): void
    {
        $tokens = (new ViewTokenizer())->tokenize(
            '<MyButton text="Save" :disabled="$isDisabled" :variant="$style" {{ $attr }} />'
        );

        $this->assertSame(ViewTokenType::StartTag, $tokens[0]->type);
        $this->assertCount(4, $tokens[0]->attributes);
        $this->assertSame("text", $tokens[0]->attributes[0]->name);
        $this->assertSame("disabled", $tokens[0]->attributes[1]->name);
        $this->assertSame("variant", $tokens[0]->attributes[2]->name);
        $this->assertInstanceOf(AttributeSpreadNode::class, $tokens[0]->attributes[3]);
        $this->assertSame('$attr', $tokens[0]->attributes[3]->expression);
    }

    public function testThrowsForBoundAttributeWithoutValue(): void
    {
        $this->expectException(ViewParseException::class);

        (new ViewTokenizer())->tokenize('<MyButton :disabled />');
    }

    public function testTokenizesScriptContentAsRawText(): void
    {
        $tokens = (new ViewTokenizer())->tokenize(
            '<script>this.innerHTML = `<strong>${this.dataset.title}</strong>`;</script>'
        );

        $this->assertSame(ViewTokenType::StartTag, $tokens[0]->type);
        $this->assertSame("script", $tokens[0]->value);
        $this->assertSame(ViewTokenType::RawText, $tokens[1]->type);
        $this->assertSame('this.innerHTML = `<strong>${this.dataset.title}</strong>`;', $tokens[1]->value);
        $this->assertSame(ViewTokenType::EndTag, $tokens[2]->type);
        $this->assertSame("script", $tokens[2]->value);
    }

    public function testTokenizesExpressions(): void
    {
        $tokens = (new ViewTokenizer())->tokenize('Hello {{ $user->name }}!');

        $this->assertSame(ViewTokenType::Text, $tokens[0]->type);
        $this->assertSame("Hello ", $tokens[0]->value);
        $this->assertSame(ViewTokenType::Expression, $tokens[1]->type);
        $this->assertSame('$user->name', $tokens[1]->value);
        $this->assertSame(ViewTokenType::Text, $tokens[2]->type);
        $this->assertSame("!", $tokens[2]->value);
    }

    public function testKeepsExpressionsInsideScriptAsRawText(): void
    {
        $tokens = (new ViewTokenizer())->tokenize('<script>const title = "{{ title }}";</script>');

        $this->assertSame(ViewTokenType::RawText, $tokens[1]->type);
        $this->assertSame('const title = "{{ title }}";', $tokens[1]->value);
    }

    public function testTokenizesDirectives(): void
    {
        $tokens = (new ViewTokenizer())->tokenize(
            '@if($show)<span>Hello</span>@elseif($maybe)<span>Maybe</span>@else Missing @endif'
        );

        $this->assertSame(ViewTokenType::Directive, $tokens[0]->type);
        $this->assertSame("if", $tokens[0]->value);
        $this->assertSame('$show', $tokens[0]->argument);
        $this->assertSame(ViewTokenType::StartTag, $tokens[1]->type);
        $this->assertSame(ViewTokenType::Directive, $tokens[4]->type);
        $this->assertSame("elseif", $tokens[4]->value);
        $this->assertSame('$maybe', $tokens[4]->argument);
        $this->assertSame(ViewTokenType::Directive, $tokens[8]->type);
        $this->assertSame("else", $tokens[8]->value);
        $this->assertNull($tokens[8]->argument);
        $this->assertSame(ViewTokenType::Text, $tokens[9]->type);
        $this->assertSame(" Missing ", $tokens[9]->value);
        $this->assertSame(ViewTokenType::Directive, $tokens[10]->type);
        $this->assertSame("endif", $tokens[10]->value);
    }

    public function testKeepsAtSignTextWhenItIsNotADirective(): void
    {
        $tokens = (new ViewTokenizer())->tokenize('Email me@example.com or use @ 42.');

        $this->assertCount(1, $tokens);
        $this->assertSame(ViewTokenType::Text, $tokens[0]->type);
        $this->assertSame('Email me@example.com or use @ 42.', $tokens[0]->value);
    }
}
