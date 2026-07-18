# Modules

[Atom Framework](Index.md)

Modules are pluggable framework or application features. A module can register bindings, routes,
components, resources, pages, commands, background jobs, and scheduled tasks.

```php
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Router\RouteEntry;

final class BlogToolsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->route(RouteEntry::get(
            $context->mountedPath(),
            fn(): string => "Blog tools"
        ));
    }
}
```

Register modules from the application `modules()` hook:

```php
use Atom\Module\ModuleRegistry;

protected function modules(ModuleRegistry $modules): void
{
    $modules->add(new BlogToolsModule(), "/tools/blog");
}
```

Modules are registered after the injector and router are ready and before `bootstrap()` runs. `registerModule()` is still available when a module must be registered manually.

`ModuleContext` currently exposes:

```php
$context->router;
$context->injector;
$context->components;
$context->bindings;
$context->basePath;

$context->mountedPath();
$context->mountedPath("/resources/app.css");
$context->resourcePath();
$context->resourcePath("/assets", "app.css");
$context->root();
$context->bind(SomeService::class)->toSelf()->singleton();
$context->route($entry);
$context->component("TagName", ComponentClass::class);
$context->component("Feature.AppShell", AppShell::class);
$context->commands(__DIR__ . "/Commands", __NAMESPACE__ . "\\Commands");
$context->jobs(FeatureJob::class);
$context->schedule(static function (Schedule $schedule): void {
    $schedule->command("feature:cleanup")->daily();
});
$context->pages(__DIR__ . "/UI/Pages", [
    ModulePageMiddleware::class,
]);
$context->resources("/resources", __DIR__ . "/UI/Resources");
```

Command directories registered through `ModuleContext::commands()` are added to the normal console discovery sources. Commands can extend `Command` or expose methods marked with `#[ConsoleCommand]`. Only installed modules contribute their commands.

```php
final readonly class FeatureCommands
{
    #[ConsoleCommand("feature:publish", "Publish feature scaffolding")]
    public function publish(): int
    {
        return 0;
    }
}
```

Register module commands during normal application initialization, before `ConsoleApplication` is first resolved.

The module convention is:

```text
Feature/
  UI/
    Pages/
    Components/
    Resources/
```

Resources are served through the framework static file handler. A resource directory registers one wildcard `GET` route under the given path. With a module base path of `/tools/blog`, this call:

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

Use `resourcePath()` when a component or page needs to render a URL to a mounted resource:

```php
$context->resourcePath(); // /tools/blog/resources
$context->resourcePath("/assets", "app.css"); // /tools/blog/assets/app.css
```

Registering the same resource route more than once returns the existing route instead of creating a duplicate.

Use `root()` when a module needs to register shared routes or resources outside its mounted path:

```php
Client::resources($context->root());
```

The optional client module serves the Atom browser runtime and MorphDOM integration:

```php
use Atom\Modules\Client\Client;

Client::resources($context);
```

Its default URLs are:

```text
/atom/client/resources/atom.js
/atom/client/resources/morphdom.js
/atom/client/resources/atom-morphdom.js
```

The optional components module registers the shared PHP component tags and serves their CSS and icons:

```php
use Atom\Modules\Components\Components;

$modules->add(Components::module());
```

Its resources are exposed at:

```text
/atom/components/resources/atom.css
/atom/components/resources/icons/...
```

Register either module independently, or register both when an application uses both layers.

Pages discovered through `pages()` are registered under the context base path.

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

Modules can also register services into the application container:

```php
final class BlogToolsModule implements ModuleInterface
{
    public function register(ModuleContext $context): void
    {
        $context->bind(BlogIndexer::class)->toSelf()->singleton();
    }
}
```

Module bindings are added to the same application container used by routes, pages, command handlers, and components.

If multiple providers register the same token, the last binding wins as long as the token has not already been resolved as a singleton. In practice, modules should avoid surprising collisions, but intentional overrides are allowed:

```php
$context->bind(Mailer::class)->to(FakeMailer::class);
```
