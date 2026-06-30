# Pages and View Engine

[Atom Framework](Index.md)

Pages are the preferred way to build browser-facing screens.

A page is a PHP class plus an adjacent `.atom.html` template:

```text
app/Pages/
├── HomePage.php
└── HomePage.atom.html
```

## Register Pages

Register page directories from the application `pages()` hook:

```php
use Atom\Page\PageRegistry;

protected function pages(PageRegistry $pages): void
{
    $pages->directory("@app/Pages");
}
```

You can also mount a page directory under a route prefix:

```php
$pages->directory("@app/AdminPages", "/admin");
```

`Page::registerPages()` is still available for manual registration.

Page templates and `TemplateComponent` `.atom.html` templates are parsed into an AST and cached in memory by template path and modified time. Rendering stays dynamic; only the parsed template structure is reused.

## Page Class

```php
namespace App\Pages;

use Atom\Page\PageRoute;

#[PageRoute("/", name: "home")]
final class HomePage extends AppPage
{
    public string $title = "Atom Sample";

    public function get(): void
    {
        // Load page state before rendering.
    }
}
```

The method matching the request method is invoked before rendering:

```php
public function get(): void {}
public function post(Request $request, Response $response): Response {}
public function put(): mixed {}
public function delete(): mixed {}
```

If the method returns a non-null result, that result is used directly.
If it returns `null` or `void`, the page template is rendered.

Route parameters are passed into the page method by name:

```php
#[PageRoute("/articles/{id}")]
final class ArticleDetailsPage extends AppPage
{
    public function get(string $id): void
    {
    }
}
```

## Page Templates

`HomePage.atom.html`:

```html
<section class="hero">
    <h1>{{ $this->title }}</h1>
</section>
```

The page instance is available as both:

```php
$this
$page
```

Expressions are escaped by default:

```html
{{ $article->title }}
```

Attributes support interpolation:

```html
<a href="/articles/{{ $article->id }}">{{ $article->title }}</a>
```

## Directives

Conditionals:

```html
@if($articleCount > 0)
    <p>Articles found.</p>
@elseif($loading)
    <p>Loading...</p>
@else
    <p>No articles yet.</p>
@endif
```

Loops:

```html
@foreach($this->articles as $article)
    <article>{{ $article->title }}</article>
@endforeach
```

## Layouts

Pages can specify a layout component class.

```php
namespace App\Pages;

use App\Components\Layout;
use Atom\Page\Page;

abstract class AppPage extends Page
{
    public ?string $layout = Layout::class;

    public string $title = "Atom Sample";
}
```

The page template stays body-only. `PageRenderer` renders the page body first, then passes it to the layout component as default content.

The layout component receives:

```php
public Page $page;
public ?Fragment $content = null;
```

## Components

Components are documented separately:

- [Components](Components.md)

## Native Component Templates

For components that should render with plain PHP, use `ComponentView`.

```php
final class Layout implements ComponentInterface
{
    public Page $page;
    public ?Fragment $content = null;

    public function render(): string
    {
        return ComponentView::render($this);
    }
}
```

Adjacent template:

```text
Layout.atom.php
```

Template variables:

```php
<?php

/** @var \App\Components\Layout $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

?>
<title><?= $context->encode($component->title()) ?></title>
<?= $context->fragment($component->content) ?>
```

The context provides:

```php
$context->encode($value)
$context->fragment($fragment, $variables = [])
$context->attributes([...])
$context->classes([...])
```
