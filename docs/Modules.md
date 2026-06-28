# Modules

[Atom Framework](Index.md)

Modules are pluggable framework or application features. A module can register routes, components, resources, pages, and later services or commands.

```php
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;

final class BlogToolsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->route(RouteEntry::route(
            "GET",
            "/tools/blog",
            RouteAction::fromClosure(fn(): string => "Blog tools")
        ));
    }
}
```

Register a module from application bootstrap:

```php
$this->registerModule(new BlogToolsModule(), "/tools/blog");
```

`ModuleContext` currently exposes:

```php
$context->router;
$context->injector;
$context->components;
$context->basePath;

$context->route($entry);
$context->component("TagName", ComponentClass::class);
$context->component("Feature.AppShell", AppShell::class);
$context->pages(__DIR__ . "/UI/Pages");
$context->resources("/resources", __DIR__ . "/UI/Resources");
$context->withBasePath("/tools/blog")->pages(__DIR__ . "/UI/Pages");
```

The module convention is:

```text
Feature/
  UI/
    Pages/
    Components/
    Resources/
```

Resources are registered as exact `GET` routes for files found in the resource directory. With a module base path of `/tools/blog`, this call:

```php
$context->resources("/resources", __DIR__ . "/UI/Resources");
```

serves:

```text
/tools/blog/resources/app.css
```

for:

```text
UI/Resources/app.css
```

Pages discovered through `pages()` are registered under the context base path. A module can create a child context when it wants to mount internal pages under its own route prefix:

```php
$context->withBasePath("/tools/blog")->pages(__DIR__ . "/UI/Pages");
```

Then a page declared with:

```php
#[PageRoute("/settings")]
```

is served from:

```text
/tools/blog/settings
```

Components can use dotted names to keep module UI tags grouped:

```php
$context->component("BlogTools.AppShell", AppShell::class);
```

Then a module page template can render:

```html
<BlogTools.AppShell>
    <h1>{{ $this->title }}</h1>
</BlogTools.AppShell>
```
