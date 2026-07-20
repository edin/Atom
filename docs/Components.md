# Components

[Atom Framework](Index.md)

Components are the reusable building blocks of `.atom.html` views.

They are intentionally simple PHP objects:

- attributes hydrate public properties
- default child content hydrates `public ?Fragment $content` or `public ?TemplateFragment $content`
- named fragments hydrate matching `Fragment` or `TemplateFragment` properties
- unknown attributes can be captured with `AttributeBag`
- declared child tags can be collected into typed arrays with `#[Children]`
- child fragments are lazy, so the parent decides if and when they render

## Register Components

Register a component when you want a short tag name:

```php
use App\Components\Button;
use Atom\View\Component\ComponentRegistry;

protected function components(ComponentRegistry $components): void
{
    $components->register("Button", Button::class);
}
```

Then use it from a template:

```html
<Button label="Save" />
```

## Component Sets

Use `ComponentSet` when a package or module provides several tag mappings as one reusable definition:

```php
use Atom\View\Component\ComponentSet;

final readonly class BlogComponents
{
    public static function definitions(): ComponentSet
    {
        return ComponentSet::from([
            "Blog.ArticleCard" => ArticleCard::class,
            "Blog.CategoryBadge" => CategoryBadge::class,
        ]);
    }
}
```

A registry can import the complete set:

```php
$components->import(BlogComponents::definitions());
```

Modules import sets through their context:

```php
public function register(ModuleContext $context): void
{
    $context->importComponents(BlogComponents::definitions());
}
```

Sets are immutable. Add a definition by creating a derived set with `with()`, or combine disjoint sets with `merge()`. Duplicate tags and classes that do not implement `ComponentInterface` fail immediately. Registry imports are atomic: if any imported tag is already registered, none of the set is added.

The built-in `Components`, `Client`, `Accounts`, and `ApiExplorer` modules expose their mappings through a `definitions()` method. This contract will also allow module-local registries to import shared component sets without exporting them to the application.

Framework internals may also use component class names directly, but application templates should usually prefer registered names.

The Components module registers its default UI and form component set:

```html
<Button>Save</Button>
<Button variant="link" atom:action="edit(1)">Edit</Button>
<Alert variant="danger">Could not save.</Alert>
<Badge>Published</Badge>
<Avatar name="Ada Lovelace" />
<Tag variant="success">Stable</Tag>
<StatusDot variant="success" label="Online" />
<Kbd>Ctrl</Kbd>
<Divider>or</Divider>
<Details summary="Advanced options">...</Details>
<Panel title="Articles">...</Panel>
<Stack gap="sm">...</Stack>
<Inline align="center" justify="between">...</Inline>
<Form submit="save" csrf>...</Form>
<FormActions>...</FormActions>
<Field label="Title" name="title">...</Field>
<TextInput name="title" maxlength="120" />
<TextArea name="body" rows="8" />
<TextField label="Title" name="title" maxlength="120" />
<TextAreaField label="Body" name="body" />
<SelectField label="Category" name="category_id" bind="categoryId" :options="$this->categories" />
<CheckField label="Published" name="isPublished" />
<HiddenField name="id" />
<FieldError name="title" />
<ValidationSummary />
```

Use the optional asset components from an `.atom.html` layout when the Client and Components modules are registered:

```html
<!doctype html>
<html lang="en">
<head>
    <ComponentsStyles />
</head>
<body>
    {{ $context->fragmentHtml($this->content) }}
    <ClientScripts morphdom />
</body>
</html>
```

`ClientScripts` always loads the Atom runtime. Set `morphdom` to also load MorphDOM and the Atom adapter. Both asset components accept a custom `resource-path`; their default paths match the module defaults.

`Avatar` renders an image when `src` is provided and otherwise derives up to two initials from `name`. `StatusDot` requires a `label` when its meaning is not also written next to it. `Details` uses native `<details>` disclosure behavior and does not require JavaScript.

Use `Badge` for compact status or metadata, and `Tag` for pill-shaped classification labels. `Divider` supports `horizontal` and `vertical` orientations.

`TextInput` and `TextArea` read values from the current page property matching `name`, unless a `value` attribute is provided. They render framework control classes such as `atom-input` and `atom-textarea`. When validation errors exist, they render `aria-invalid`, `aria-describedby`, and the `is-invalid` class.

`TextField`, `TextAreaField`, `SelectField`, and `CheckField` are composite field entries. They render the field wrapper, control, validation state, and field error.

`CheckField` renders a hidden unchecked fallback value before the checkbox, so boolean form models receive `"0"` when the box is unchecked and `"1"` when checked.

`HiddenField` binds from the current page or form model like the other field components, but renders only a hidden input without visible field chrome.

Set `csrf` on a non-GET `Form` to prepend the session-backed `_token` field. The target route or route group must use `CsrfMiddleware` to validate it.

## Properties

Attributes are assigned to public properties.

```php
use Atom\View\Component\ComponentInterface;

final class Button implements ComponentInterface
{
    public string $label = "";
    public bool $disabled = false;

    public function render(): string
    {
        $disabled = $this->disabled ? " disabled" : "";

        return "<button{$disabled}>{$this->label}</button>";
    }
}
```

```html
<Button label="Save" :disabled="$saving" />
```

Attribute names are normalized to property names:

```html
<Button button-kind="primary" />
```

hydrates:

```php
public string $buttonKind = "";
```

Required public typed properties without defaults must be provided:

```php
public string $label;
```

## Attribute Bag

Use `AttributeBag` when a component should forward arbitrary HTML attributes.

```php
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Button implements ComponentInterface
{
    public AttributeBag $attributes;
    public ?Fragment $content = null;

    public function render(): string
    {
        return Html::tag("button", $this->attributes->all(), $this->content?->render() ?? "");
    }
}
```

```html
<Button id="save" class="primary" disabled>Save</Button>
```

`AttributeBag` properties are initialized automatically during component hydration. Components only need to declare:

```php
public AttributeBag $attributes;
```

## HTML Helpers

Small framework components are usually built with `Html` helpers instead of hand-written string concatenation.

```php
use Atom\View\Html;

return Html::tag("button", Html::mergeAttributes([
    "type" => "button",
    "class" => Html::classes("atom-button", $this->class),
    "data-variant" => $this->variant,
], $this->attributes->all()), $this->content?->render() ?? "");
```

Available helpers:

```php
Html::escape($value)
Html::attribute("disabled", true)
Html::attributes(["class" => "button", "disabled" => true])
Html::tag("div", ["class" => "panel"], $content)
Html::voidTag("input", ["name" => "title"])
Html::classes("atom-button", ["is-active" => $active], $extraClass)
Html::mergeAttributes($defaults, $attributes)
```

`Html::tag()` does not escape content. Pass escaped text or rendered HTML intentionally.

## Default Content

Default child content is assigned to `content`.

```php
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;

final class Card implements ComponentInterface
{
    public ?Fragment $content = null;

    public function render(): string
    {
        return "<section>" . $this->content?->render() . "</section>";
    }
}
```

```html
<Card>
    <p>Hello {{ $name }}</p>
</Card>
```

Fragments are lazy. The component controls when the content is rendered and what variables are available:

```php
$this->content?->render(["name" => "Ada"]);
```

This is also how parent components can provide data to their child content. `Form`, for example, passes a `model` variable into its child fragment when `model` is set.

Useful helpers:

```php
$this->content?->isEmpty();
$this->content?->renderOr("<p>Empty</p>");
```

Whitespace-only output counts as empty.

Use `TemplateFragment` when a parent component needs to inspect the child template as well as render it.
This is useful for showcase/documentation components that render a live preview and source code:

```php
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\TemplateFragment;
use Atom\View\Html;

final class ComponentExample implements ComponentInterface
{
    public string $title = "";
    public ?TemplateFragment $content = null;

    public function render(): string
    {
        return Html::tag("section", ["class" => "component-example"],
            Html::tag("h2", content: Html::escape($this->title)) .
            Html::tag("div", ["class" => "component-example-preview"], $this->content?->render() ?? "") .
            Html::tag("pre", content: Html::escape($this->content?->source() ?? ""))
        );
    }
}
```

Template fragments expose:

```php
$this->content?->render($variables)
$this->content?->source()
$this->content?->nodes()
$this->content?->fragment()
```

The first version of `source()` serializes the parsed AST. It is intended for readable examples, not exact whitespace preservation.

## Context Injection

Use `#[FromContext]` when a child component should receive a value provided by a parent fragment render.

```php
use Atom\View\Component\FromContext;

final class TextField extends FieldEntry
{
    #[FromContext("model")]
    public mixed $model = null;
}
```

Explicit attributes win over context values:

```html
<TextField :model="$otherModel" name="title" />
```

Without an explicit attribute, context can fill the property:

```html
<Form :model="$this->editForm" submit="save">
    <TextField label="Title" name="title" />
    <TextAreaField label="Body" name="body" />
</Form>
```

`Form` renders its child fragment with:

```php
$this->content?->render(["model" => $this->model]);
```

`FieldEntry` uses the context model before falling back to the page property. This avoids prop drilling in form components while keeping data flow explicit.

## Named Fragments

Named fragments use dotted child elements.

```php
final class Panel implements ComponentInterface
{
    public ?Fragment $header = null;
    public ?Fragment $content = null;

    public function render(): string
    {
        return "<section>"
            . "<header>" . $this->header?->render() . "</header>"
            . "<div>" . $this->content?->render() . "</div>"
            . "</section>";
    }
}
```

```html
<Panel>
    <Panel.Header>
        <h2>Users</h2>
    </Panel.Header>

    <p>Panel body.</p>
</Panel>
```

`<Panel.Header>` hydrates `public ?Fragment $header` or `public ?TemplateFragment $header`.

## Typed Children

Use `#[Children]` when child tags should become typed objects instead of immediate output.

This is useful for components like tables, menus, tabs, forms, or anything where the parent interprets child declarations.

```php
use Atom\View\Component\Children;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;

final class Table implements ComponentInterface
{
    /** @var Column[] */
    #[Children("Column", Column::class)]
    public array $columns = [];

    /** @var array<object> */
    public array $items = [];

    public function render(): string
    {
        $html = "<table><thead><tr>";

        foreach ($this->columns as $column) {
            $html .= "<th>" . $column->header() . "</th>";
        }

        $html .= "</tr></thead><tbody>";

        foreach ($this->items as $item) {
            $html .= "<tr>";

            foreach ($this->columns as $column) {
                $html .= "<td>" . $column->cell()->render(["item" => $item]) . "</td>";
            }

            $html .= "</tr>";
        }

        return $html . "</tbody></table>";
    }
}

final class Column implements ComponentInterface
{
    public string $title = "";
    public ?Fragment $header = null;
    public ?Fragment $cell = null;
    public ?Fragment $content = null;

    public function render(): string
    {
        return "";
    }

    public function header(): string
    {
        return $this->header?->renderOr($this->title) ?? $this->title;
    }

    public function cell(): Fragment
    {
        return $this->cell ?? $this->content ?? new Fragment(static fn(): string => "");
    }
}
```

Template:

```html
<Table :items="$users">
    <Column title="Name">
        {{ $item->name }}
    </Column>

    <Column>
        <Column.Header>Email</Column.Header>
        <Column.Cell>{{ $item->email }}</Column.Cell>
    </Column>
</Table>
```

`Column` is not rendered automatically. It is collected into `Table::$columns`, and `Table` decides:

- whether each column renders
- where each column renders
- how many times each fragment renders
- which variables each fragment receives

This keeps child components useful as declarative configuration, not only as standalone HTML emitters.

## Render Results

A component may return:

```php
string
TemplateNode
ViewNodeInterface
array<ViewNodeInterface>
```

Most application components return strings or use a native component template.

## Atom HTML Component Templates

For `.atom.html` component templates, extend `TemplateComponent` and place a template with the same base filename beside the component class:

```php
use Atom\View\Component\TemplateComponent;

final class EndpointList extends TemplateComponent
{
    public array $endpoints = [];
}
```

```text
EndpointList.php
EndpointList.atom.html
```

The template receives:

```php
$this
$component
$context
```

and public component properties as variables.

Use `$context->fragmentHtml($this->content)` when a template component needs to render a child fragment as HTML.

## Native Component Templates

For larger HTML output, a component can render an adjacent `.atom.php` template.

```php
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentView;
use Atom\View\Component\Fragment;

final class Layout implements ComponentInterface
{
    public string $title = "";
    public ?Fragment $content = null;

    public function render(): string
    {
        return ComponentView::render($this);
    }
}
```

Adjacent file:

```text
Layout.atom.php
```

Template:

```php
<?php

/** @var \App\Components\Layout $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

?>
<title><?= $context->encode($component->title) ?></title>
<?= $context->fragment($component->content) ?>
```

The template context provides:

```php
$context->encode($value)
$context->fragment($fragment, $variables = [])
$context->attributes([...])
$context->classes([...])
```

## Error Messages

Component authoring errors are intended to point at the fix.

Examples:

- unknown attributes suggest adding a public property or `AttributeBag`
- unknown named fragments suggest adding the matching `Fragment` or `TemplateFragment` property
- body content without `public ?Fragment $content` or `public ?TemplateFragment $content` fails clearly
- invalid `#[Children]` mappings name the mapped property
- invalid render results include the returned type
