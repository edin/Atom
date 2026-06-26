<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentView;
use Atom\View\Component\Fragment;
use PHPUnit\Framework\TestCase;

final class ComponentViewTest extends TestCase
{
    public function testRendersTemplateNextToComponentClass(): void
    {
        $component = new TemplateBackedComponent();
        $component->title = 'Hello <Atom>';
        $component->content = new Fragment(static fn(array $variables = []): string => "Content for " . $variables["name"]);

        $html = $component->render();

        $this->assertSame("<section><h1>Hello &lt;Atom&gt;</h1>Content for Ada</section>\n", $html);
    }

    public function testTemplateContextProvidesHelpers(): void
    {
        $component = new ContextBackedComponent();
        $component->title = 'Hello <Atom>';
        $component->content = new Fragment(static fn(): string => "Body");

        $html = $component->render();

        $this->assertSame('<article class="card is-active" data-id="42"><h1>Hello &lt;Atom&gt;</h1>Body</article>' . "\n", $html);
    }
}

final class TemplateBackedComponent implements ComponentInterface
{
    public string $title = "";

    public ?Fragment $content = null;

    public function render(): string
    {
        return ComponentView::render($this, "ComponentFixtures/TemplateBackedComponent.atom.php");
    }
}

final class ContextBackedComponent implements ComponentInterface
{
    public string $title = "";

    public ?Fragment $content = null;

    public function render(): string
    {
        return ComponentView::render($this, "ComponentFixtures/ContextBackedComponent.atom.php");
    }
}
