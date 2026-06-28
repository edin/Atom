# Components

[Atom Framework](Index.md)

Components are the reusable building blocks of `.atom.html` views.

They are intentionally simple PHP objects:

- attributes hydrate public properties
- default child content hydrates `public ?Fragment $content`
- named fragments hydrate matching `Fragment` properties
- unknown attributes can be captured with `AttributeBag`
- declared child tags can be collected into typed arrays with `#[Children]`
- child fragments are lazy, so the parent decides if and when they render

## Register Components

Register a component when you want a short tag name:

```php
use App\Components\Button;
use Atom\View\Component\ComponentRegistry;

$registry->register("Button", Button::class);
```

Then use it from a template:

```html
<Button label="Save" />
```

Framework internals may also use component class names directly, but application templates should usually prefer registered names.

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

final class Button implements ComponentInterface
{
    public AttributeBag $attributes;
    public ?Fragment $content = null;

    public function render(): string
    {
        return "<button{$this->attributes->render()}>"
            . $this->content?->render()
            . "</button>";
    }
}
```

```html
<Button id="save" class="primary" disabled>Save</Button>
```

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

Useful helpers:

```php
$this->content?->isEmpty();
$this->content?->renderOr("<p>Empty</p>");
```

Whitespace-only output counts as empty.

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

`<Panel.Header>` hydrates `public ?Fragment $header`.

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
ViewNode
array<ViewNode>
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
- unknown named fragments suggest adding the matching `Fragment` property
- body content without `public ?Fragment $content` fails clearly
- invalid `#[Children]` mappings name the mapped property
- invalid render results include the returned type
