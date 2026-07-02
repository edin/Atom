<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Html;
use PHPUnit\Framework\TestCase;

final class HtmlTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        $this->assertSame("&lt;Atom &amp; 'PHP'&gt;", Html::escape("<Atom & 'PHP'>"));
    }

    public function testRendersHtmlAttributes(): void
    {
        $this->assertSame(
            ' id="title" required value="Atom &amp; PHP"',
            Html::attributes([
                "id" => "title",
                "disabled" => false,
                "hidden" => null,
                "class" => "",
                "required" => true,
                "value" => "Atom & PHP",
            ])
        );
    }

    public function testRendersSingleHtmlAttribute(): void
    {
        $this->assertSame(" checked", Html::attribute("checked", true));
        $this->assertSame("", Html::attribute("disabled", false));
        $this->assertSame(' title="Hello &lt;Atom&gt;"', Html::attribute("title", "Hello <Atom>"));
    }

    public function testRendersHtmlTag(): void
    {
        $this->assertSame(
            '<div class="notice">Hello <strong>Atom</strong></div>',
            Html::tag("div", ["class" => "notice"], "Hello <strong>Atom</strong>")
        );
    }

    public function testRendersHtmlVoidTag(): void
    {
        $this->assertSame(
            '<input type="text" name="title" required>',
            Html::voidTag("input", ["type" => "text", "name" => "title", "required" => true])
        );
    }

    public function testBuildsClassList(): void
    {
        $this->assertSame(
            "atom-button is-active custom",
            Html::classes("atom-button", ["is-active" => true, "is-hidden" => false], "custom atom-button")
        );
    }

    public function testMergesAttributesWithClassAppend(): void
    {
        $this->assertSame([
            "class" => "atom-button custom",
            "type" => "submit",
            "data-id" => "42",
        ], Html::mergeAttributes([
            "class" => "atom-button",
            "type" => "button",
        ], [
            "class" => "custom",
            "type" => "submit",
            "data-id" => "42",
        ]));
    }
}
