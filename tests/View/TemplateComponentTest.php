<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Application;
use Atom\Profiler\Profile;
use Atom\View\Component\TemplateComponent;
use Atom\View\Component\Fragment;
use Atom\View\Component\ComponentTemplateContext;
use Atom\View\Templates;
use Atom\View\Render\ViewRenderer;
use PHPUnit\Framework\TestCase;

final class TemplateComponentTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Profile::reset();
        Templates::reset();
    }

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

    public function testTemplateComponentCachesParsedTemplate(): void
    {
        $component = new TestTemplateComponent();
        $component->title = "Hello";
        $renderer = new ViewRenderer();

        $first = $renderer->render($component->render(), [
            "this" => $component,
            "context" => new ComponentTemplateContext(),
        ]);
        $second = $renderer->render($component->render(), [
            "this" => $component,
            "context" => new ComponentTemplateContext(),
        ]);

        $parseSpans = array_filter(
            Profile::profiler()->spans(),
            static fn($span): bool => $span->name === "view.parse"
        );

        $this->assertSame($first, $second);
        $this->assertCount(1, $parseSpans);
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
