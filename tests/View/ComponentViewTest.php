<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\Alert;
use Atom\Modules\Framework\Components\Badge;
use Atom\Modules\Framework\Components\Button;
use Atom\Modules\Framework\Components\Field;
use Atom\Modules\Framework\Components\Form;
use Atom\Modules\Framework\Components\Panel;
use Atom\Modules\Framework\Components\SelectField;
use Atom\Modules\Framework\Components\FormActions;
use Atom\Modules\Framework\Components\Inline;
use Atom\Modules\Framework\Components\Stack;
use Atom\Modules\Framework\Components\TextArea;
use Atom\Modules\Framework\Components\TextAreaField;
use Atom\Modules\Framework\Components\TextField;
use Atom\Modules\Framework\Components\TextInput;
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

        $this->assertStringContainsString('<p id="title-error" class="field-error">The field is required.</p>', $html);
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

    public function testFrameworkInputComponentsBindPageValuesAndValidationState(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "";
        $page->body = "Hello <Atom>";
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("TextInput", TextInput::class);
        $registry->register("TextArea", TextArea::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<TextInput name="title" maxlength="120" /><TextArea name="body" rows="5" />'),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<input type="text" id="title" name="title" class="is-invalid" aria-invalid="true" aria-describedby="title-error" maxlength="120">',
            $html
        );
        $this->assertStringContainsString(
            '<textarea id="body" name="body" rows="5">Hello &lt;Atom&gt;</textarea>',
            $html
        );
    }

    public function testFrameworkInputComponentsRenderWithoutExtraAttributes(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "Atom";
        $page->body = "Body";

        $registry = new ComponentRegistry();
        $registry->register("TextInput", TextInput::class);
        $registry->register("TextArea", TextArea::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<TextInput name="title" /><TextArea name="body" />'),
            ["page" => $page]
        );

        $this->assertStringContainsString('<input type="text" id="title" name="title" value="Atom">', $html);
        $this->assertStringContainsString('<textarea id="body" name="body">Body</textarea>', $html);
    }

    public function testFrameworkButtonComponentRendersButtonAndLink(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Button", Button::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Button variant="danger" class="compact" atom:action="delete(12)">Delete</Button>' .
                '<Button href="/articles" variant="ghost">Articles</Button>' .
                '<Button variant="link-danger" atom:action="askDelete(12)">Delete link</Button>'
            )
        );

        $this->assertStringContainsString(
            '<button type="button" class="atom-button compact" data-variant="danger" atom:action="delete(12)">Delete</button>',
            $html
        );
        $this->assertStringContainsString(
            '<a href="/articles" class="atom-button" data-variant="ghost">Articles</a>',
            $html
        );
        $this->assertStringContainsString(
            '<button type="button" class="atom-button" data-variant="link-danger" atom:action="askDelete(12)">Delete link</button>',
            $html
        );
    }

    public function testFrameworkAlertComponentRendersEscapedContent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Alert", Alert::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Alert variant="danger" text="Careful &lt;Atom&gt;" />')
        );

        $this->assertSame(
            '<div class="atom-alert" data-variant="danger" role="status">Careful &amp;lt;Atom&amp;gt;</div>',
            $html
        );
    }

    public function testFrameworkBadgeComponentRendersVariantAndContent(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Badge", Badge::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Badge variant="danger" class="compact">Draft</Badge>')
        );

        $this->assertSame(
            '<span class="atom-badge compact" data-variant="danger">Draft</span>',
            $html
        );
    }

    public function testFrameworkPanelComponentRendersTitleAndBody(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Panel", Panel::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse('<Panel title="Stats"><p>Hello</p></Panel>')
        );

        $this->assertStringContainsString('<section class="atom-panel">', $html);
        $this->assertStringContainsString('<header class="atom-panel__header"><h2 class="atom-panel__title">Stats</h2></header>', $html);
        $this->assertStringContainsString('<div class="atom-panel__body"><p>Hello</p></div>', $html);
    }

    public function testFrameworkLayoutComponentsRenderContentAndOptions(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Stack", Stack::class);
        $registry->register("Inline", Inline::class);
        $registry->register("FormActions", FormActions::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Stack gap="sm"><p>A</p></Stack>' .
                '<Inline gap="lg" align="center" justify="between"><span>B</span></Inline>' .
                '<FormActions align="end"><button>Save</button></FormActions>'
            )
        );

        $this->assertStringContainsString('<div class="atom-stack" data-gap="sm"><p>A</p></div>', $html);
        $this->assertStringContainsString(
            '<div class="atom-inline" data-gap="lg" data-align="center" data-justify="between"><span>B</span></div>',
            $html
        );
        $this->assertStringContainsString(
            '<div class="atom-form-actions" data-align="end"><button>Save</button></div>',
            $html
        );
    }

    public function testFrameworkFormAndFieldComponentsRenderStructure(): void
    {
        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("Field", Field::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Form submit="save" class="compact">' .
                '<Field label="Title" name="title"><input id="title"></Field>' .
                '</Form>'
            )
        );

        $this->assertSame(
            '<form method="post" atom:submit="save" class="atom-form compact">' .
            '<label class="atom-field" for="title"><span class="atom-field__label">Title</span><input id="title" /></label>' .
            '</form>',
            $html
        );
    }

    public function testFrameworkCompositeFieldComponentsRenderInputsAndErrors(): void
    {
        $page = new ValidationComponentPage();
        $page->title = "";
        $page->body = "Hello <Atom>";
        $page->validate();

        $registry = new ComponentRegistry();
        $registry->register("TextField", TextField::class);
        $registry->register("TextAreaField", TextAreaField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<TextField label="Title" name="title" maxlength="120" />' .
                '<TextAreaField label="Body" name="body" rows="4" />'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<label class="atom-field" for="title"><span class="atom-field__label">Title</span><input type="text" id="title" name="title" class="is-invalid" aria-invalid="true" aria-describedby="title-error" maxlength="120"><p id="title-error" class="field-error">The field is required.</p></label>',
            $html
        );
        $this->assertStringContainsString(
            '<label class="atom-field" for="body"><span class="atom-field__label">Body</span><textarea id="body" name="body" rows="4">Hello &lt;Atom&gt;</textarea></label>',
            $html
        );
    }

    public function testFrameworkSelectFieldRendersOptionsFromObjects(): void
    {
        $page = new ValidationComponentPage();
        $page->categoryId = 2;
        $page->categories = [
            (object) ["id" => 1, "name" => "News"],
            (object) ["id" => 2, "name" => "Updates"],
        ];

        $registry = new ComponentRegistry();
        $registry->register("SelectField", SelectField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<SelectField label="Category" name="category_id" bind="categoryId" optionValue="id" optionText="name" :options="$page->categories" />'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString(
            '<label class="atom-field" for="category_id"><span class="atom-field__label">Category</span><select id="category_id" name="category_id" class="atom-select">',
            $html
        );
        $this->assertStringContainsString('<option value="1">News</option>', $html);
        $this->assertStringContainsString('<option value="2" selected>Updates</option>', $html);
    }

    public function testFrameworkFormProvidesModelContextToFields(): void
    {
        $page = new ValidationComponentPage();
        $page->edit = (object) [
            "title" => "Model title",
            "body" => "Model body",
        ];

        $registry = new ComponentRegistry();
        $registry->register("Form", Form::class);
        $registry->register("TextField", TextField::class);
        $registry->register("TextAreaField", TextAreaField::class);

        $html = (new ViewRenderer(components: $registry))->render(
            (new ViewParser())->parse(
                '<Form :model="$page->edit">' .
                '<TextField label="Title" name="title" />' .
                '<TextAreaField label="Body" name="body" />' .
                '</Form>'
            ),
            ["page" => $page]
        );

        $this->assertStringContainsString('name="title" value="Model title"', $html);
        $this->assertStringContainsString('<textarea id="body" name="body">Model body</textarea>', $html);
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

    public string $body = "";

    public int $categoryId = 0;

    public array $categories = [];

    public object $edit;
}
