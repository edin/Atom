<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Ast\ElementNode;
use Atom\View\Ast\AttributeSpreadNode;
use Atom\View\Ast\ExpressionNode;
use Atom\View\Ast\ForEachNode;
use Atom\View\Ast\IfNode;
use Atom\View\Ast\RawTextNode;
use Atom\View\Ast\TextNode;
use Atom\View\Parser\ViewParseException;
use Atom\View\Parser\ViewParser;
use PHPUnit\Framework\TestCase;

final class ViewParserTest extends TestCase
{
    public function testParsesNestedElementsTextAndAttributes(): void
    {
        $template = (new ViewParser())->parse(
            '<div class="card" data-id=42 disabled><h1>Hello</h1><input name="title" /></div>'
        );

        $div = $template->children[0];
        $this->assertInstanceOf(ElementNode::class, $div);
        $this->assertSame("div", $div->name);
        $this->assertSame("class", $div->attributes[0]->name);
        $this->assertSame("card", $div->attributes[0]->value);
        $this->assertSame("data-id", $div->attributes[1]->name);
        $this->assertSame("42", $div->attributes[1]->value);
        $this->assertSame("disabled", $div->attributes[2]->name);
        $this->assertTrue($div->attributes[2]->value);
        $this->assertFalse($div->attributes[2]->bound);

        $heading = $div->children[0];
        $input = $div->children[1];

        $this->assertInstanceOf(ElementNode::class, $heading);
        $this->assertSame("h1", $heading->name);
        $this->assertInstanceOf(TextNode::class, $heading->children[0]);
        $this->assertSame("Hello", $heading->children[0]->text);

        $this->assertInstanceOf(ElementNode::class, $input);
        $this->assertSame("input", $input->name);
        $this->assertTrue($input->selfClosing);
    }

    public function testParsesBoundAttributes(): void
    {
        $template = (new ViewParser())->parse('<MyButton text="Save" :disabled="$isDisabled" />');

        $button = $template->children[0];

        $this->assertInstanceOf(ElementNode::class, $button);
        $this->assertSame("text", $button->attributes[0]->name);
        $this->assertSame("Save", $button->attributes[0]->value);
        $this->assertFalse($button->attributes[0]->bound);
        $this->assertSame("disabled", $button->attributes[1]->name);
        $this->assertSame('$isDisabled', $button->attributes[1]->value);
        $this->assertTrue($button->attributes[1]->bound);
    }

    public function testParsesAttributeSpreads(): void
    {
        $template = (new ViewParser())->parse(
            '<MyButton text="Save" :disabled="$isDisabled" :variant="$style" {{ $attr }} />'
        );

        $button = $template->children[0];

        $this->assertInstanceOf(ElementNode::class, $button);
        $this->assertCount(4, $button->attributes);
        $this->assertSame("text", $button->attributes[0]->name);
        $this->assertSame("disabled", $button->attributes[1]->name);
        $this->assertSame("variant", $button->attributes[2]->name);
        $this->assertInstanceOf(AttributeSpreadNode::class, $button->attributes[3]);
        $this->assertSame('$attr', $button->attributes[3]->expression);
    }

    public function testPreservesScriptAndStyleAsRawText(): void
    {
        $source = <<<'HTML'
<div>
    <script>
        customElements.define("x-card", class extends HTMLElement {
            connectedCallback() {
                this.innerHTML = `<strong>${this.dataset.title}</strong>`;
            }
        });
    </script>
    <style>
        x-card > strong { color: red; }
    </style>
</div>
HTML;

        $template = (new ViewParser())->parse($source);
        $div = $template->children[0];

        $script = $this->elements($div)[0];
        $style = $this->elements($div)[1];

        $this->assertSame("script", $script->name);
        $this->assertInstanceOf(RawTextNode::class, $script->children[0]);
        $this->assertStringContainsString('this.innerHTML = `<strong>${this.dataset.title}</strong>`;', $script->children[0]->text);

        $this->assertSame("style", $style->name);
        $this->assertInstanceOf(RawTextNode::class, $style->children[0]);
        $this->assertStringContainsString("x-card > strong", $style->children[0]->text);
    }

    public function testKeepsWebComponentsAsElementNodes(): void
    {
        $template = (new ViewParser())->parse('<my-card title="Hello"><span>Body</span></my-card>');

        $card = $template->children[0];

        $this->assertInstanceOf(ElementNode::class, $card);
        $this->assertSame("my-card", $card->name);
        $this->assertInstanceOf(ElementNode::class, $card->children[0]);
        $this->assertSame("span", $card->children[0]->name);
    }

    public function testSkipsComments(): void
    {
        $template = (new ViewParser())->parse('<div><!-- ignored --><span>Hello</span></div>');
        $div = $template->children[0];

        $this->assertCount(1, $div->children);
        $this->assertSame("span", $div->children[0]->name);
    }

    public function testParsesExpressions(): void
    {
        $template = (new ViewParser())->parse('<h1>Hello {{ $user->name }}!</h1>');

        $heading = $template->children[0];

        $this->assertInstanceOf(ElementNode::class, $heading);
        $this->assertInstanceOf(TextNode::class, $heading->children[0]);
        $this->assertSame("Hello ", $heading->children[0]->text);
        $this->assertInstanceOf(ExpressionNode::class, $heading->children[1]);
        $this->assertSame('$user->name', $heading->children[1]->expression);
        $this->assertInstanceOf(TextNode::class, $heading->children[2]);
        $this->assertSame("!", $heading->children[2]->text);
    }

    public function testParsesIfDirective(): void
    {
        $template = (new ViewParser())->parse('@if($show)<span>Hello</span>@else<span>Missing</span>@endif');

        $if = $template->children[0];

        $this->assertInstanceOf(IfNode::class, $if);
        $this->assertSame('$show', $if->condition);
        $this->assertCount(1, $if->branches);
        $this->assertCount(1, $if->then);
        $this->assertCount(1, $if->else);
        $this->assertInstanceOf(ElementNode::class, $if->then[0]);
        $this->assertSame("span", $if->then[0]->name);
        $this->assertInstanceOf(ElementNode::class, $if->else[0]);
        $this->assertSame("span", $if->else[0]->name);
    }

    public function testParsesElseIfDirective(): void
    {
        $template = (new ViewParser())->parse(
            '@if($show)<span>Hello</span>@elseif($maybe)<span>Maybe</span>@else<span>Missing</span>@endif'
        );

        $if = $template->children[0];

        $this->assertInstanceOf(IfNode::class, $if);
        $this->assertCount(2, $if->branches);
        $this->assertSame('$show', $if->branches[0]->condition);
        $this->assertSame('$maybe', $if->branches[1]->condition);
        $this->assertInstanceOf(ElementNode::class, $if->branches[0]->children[0]);
        $this->assertSame("Hello", $if->branches[0]->children[0]->children[0]->text);
        $this->assertInstanceOf(ElementNode::class, $if->branches[1]->children[0]);
        $this->assertSame("Maybe", $if->branches[1]->children[0]->children[0]->text);
        $this->assertInstanceOf(ElementNode::class, $if->else[0]);
        $this->assertSame("Missing", $if->else[0]->children[0]->text);
    }

    public function testParsesForEachDirective(): void
    {
        $template = (new ViewParser())->parse('@foreach($users as $user)<span>User</span>@endforeach');

        $forEach = $template->children[0];

        $this->assertInstanceOf(ForEachNode::class, $forEach);
        $this->assertSame('$users', $forEach->source);
        $this->assertNull($forEach->key);
        $this->assertSame('$user', $forEach->value);
        $this->assertCount(1, $forEach->children);
        $this->assertInstanceOf(ElementNode::class, $forEach->children[0]);
        $this->assertSame("span", $forEach->children[0]->name);
    }

    public function testParsesForEachDirectiveWithKey(): void
    {
        $template = (new ViewParser())->parse('@foreach($users as $id => $user)<span>User</span>@endforeach');

        $forEach = $template->children[0];

        $this->assertInstanceOf(ForEachNode::class, $forEach);
        $this->assertSame('$users', $forEach->source);
        $this->assertSame('$id', $forEach->key);
        $this->assertSame('$user', $forEach->value);
    }

    public function testThrowsForMissingEndForEachDirective(): void
    {
        $this->expectException(ViewParseException::class);

        (new ViewParser())->parse('@foreach($users as $user)<span>User</span>');
    }

    public function testThrowsForUnexpectedDirective(): void
    {
        $this->expectException(ViewParseException::class);

        (new ViewParser())->parse("@else");
    }

    public function testThrowsForMissingClosingTag(): void
    {
        $this->expectException(ViewParseException::class);

        (new ViewParser())->parse("<div><span>Hello</div>");
    }

    /**
     * @return ElementNode[]
     */
    private function elements(ElementNode $node): array
    {
        return array_values(array_filter(
            $node->children,
            static fn($child): bool => $child instanceof ElementNode
        ));
    }
}
