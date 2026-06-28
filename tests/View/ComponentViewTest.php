<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\ValidationSummary;
use Atom\Page\Page;
use Atom\Validation\Rules\Required;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use Atom\View\Component\ComponentView;
use Atom\View\Component\Fragment;
use Atom\View\Parser\ViewParser;
use Atom\View\Render\ViewRenderer;
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

    public function testFrameworkValidationComponentsRenderPageErrors(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "";
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("FieldError", FieldError::class);
        $registry->register("ValidationSummary", ValidationSummary::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<FieldError name="title" /><ValidationSummary />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<p class="field-error">The field is required.</p>', $html);
        $this->assertStringContainsString('<div class="validation-summary"><ul><li>The field is required.</li></ul></div>', $html);
    }

    public function testFrameworkValidationComponentsRenderNothingWithoutErrors(): void
    {
        $page = new ValidationComponentPage();

        $registry = new ComponentRegistry();
        $registry->register("FieldError", FieldError::class);
        $registry->register("ValidationSummary", ValidationSummary::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<FieldError name="title" /><ValidationSummary />'),
            ["page" => $page]
        );

        $this->assertSame("", $html);
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

final class ValidationComponentPage extends Page
{
    #[Required]
    public string $title = "";
}
