<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\TemplateComponent;
use Atom\View\Component\Fragment;
use Atom\View\Component\ComponentTemplateContext;
use Atom\View\Render\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class TemplateComponentTest extends TestCase
{
    public function testRendersAtomHtmlTemplateNextToComponentClass(): void
    {
        $component = new TestTemplateComponent();
        $component->title = "Hello <Atom>";

        $html = (new ViewRenderer())->render($component->render(), [
            "this" => $component,
            "context" => new ComponentTemplateContext(),
        ]);

        $this->assertSame("<section><h1>Hello &lt;Atom&gt;</h1></section>\n", $html);
    }

    public function testTemplateComponentCanRenderFragmentAsHtml(): void
    {
        $component = new TestShellTemplateComponent();
        $component->content = new Fragment(static fn(): string => "<strong>Body</strong>");

        $html = (new ViewRenderer())->render($component->render(), [
            "this" => $component,
            "context" => new ComponentTemplateContext(),
        ]);

        $this->assertSame("<main><strong>Body</strong></main>\n", $html);
    }
}

final class TestTemplateComponent extends TemplateComponent
{
    public string $title = "";
}

final class TestShellTemplateComponent extends TemplateComponent
{
    public ?Fragment $content = null;
}
