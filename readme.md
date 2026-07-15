# Atom Framework

Atom is a small PHP 8.4 framework experiment.

The current direction is:

- pages instead of browser-facing controllers
- controllers for API-style endpoints
- a tiny DI container with request-scoped context
- a custom router with route metadata
- a component-capable `.atom.html` view engine
- optional native `.atom.php` component templates
- a lightweight database/query/ORM layer with migrations and model base classes
- console command discovery

The repository now contains both the framework and a sample app:

```text
.
├── src/          Framework source
├── tests/        Framework tests
├── docs/         Framework documentation
└── sample/       Sample application
```

## Requirements

- PHP 8.4
- Composer
- PDO SQLite for the sample app

## Install

From the framework root:

```powershell
composer install
composer check
```

Run checks individually with `composer analyse` and `composer test`.

From the sample app:

```powershell
cd sample
composer install
copy .env.example .env
php atom migrate:fresh
php atom db:seed
php -S 127.0.0.1:8021 -t public public/server.php
```

Open:

```text
http://127.0.0.1:8021
```

## Sample Shape

The sample app uses the newer APIs:

```text
sample/app/
├── Application.php
├── Components/
│   ├── Layout.php
│   └── Layout.atom.php
├── Controllers/
│   └── ApiController.php
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Models/
│   ├── Article.php
│   └── Category.php
└── Pages/
    ├── AppPage.php
    ├── HomePage.php
    ├── HomePage.atom.html
    ├── ArticlesPage.php
    └── ArticlesPage.atom.html
```

`Application` registers database services, configures the active model database, attaches API controllers, and discovers pages:

```php
protected function bootstrap(Injector $injector): void
{
    Model::useDb($injector->get(Db::class));

    Route::attach(ApiController::class);

    Page::registerPages();
}
```

## First Page

```php
namespace App\Pages;

use Atom\Page\PageRoute;

#[PageRoute("/hello")]
final class HelloPage extends AppPage
{
    public string $title = "Hello";
    public string $message = "";

    public function get(): void
    {
        $this->message = "Hello from Atom.";
    }
}
```

Adjacent template:

```html
<section class="article">
    <h1>{{ $this->title }}</h1>
    <p>{{ $this->message }}</p>
</section>
```

`Page::registerPages()` discovers `app/Pages`, registers routes from `#[PageRoute]`, invokes the page method matching the request method (`get`, `post`, etc.), renders the adjacent `.atom.html`, and composes it with the page layout component when one is configured.

## First Model

```php
namespace App\Models;

use Atom\Database\Model;
use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;

#[Table("users")]
final class User extends Model
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("name")]
    public string $name;
}
```

Usage:

```php
$users = User::query()
    ->where("active", true)
    ->orderBy("name")
    ->all();

$user = User::find(1);

$user = new User();
$user->name = "Edin";
$user->save();
```

## Documentation

- [Documentation Index](docs/Index.md)
- [Pages and View Engine](docs/PagesAndViews.md)
- [Components](docs/Components.md)
- [Router](docs/Router.md)
- [Database](docs/Database.md)
- [Configuration](docs/Configuration.md)
- [Dependency Injection](docs/DependencyInjection.md)
- [Console](docs/Console.md)
- [Service Providers](docs/ServiceProviders.md)
- [Middlewares](docs/Middlewares.md)
- [Validation](docs/Validation.md)
