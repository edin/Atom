# Atom Framework

Simple PHP Framework

```
    git clone https://github.com/edin/AtomApp.git
    composer update 
    php -S localhost:5000 -t public
    http://localhost:5000
```

# Project goals

- Simple php framework
- Simple routing
- Simple dependency injection container
- Simple templates
- Simple validation
- Small HTTP request/response wrappers
- Middleware pipeline support

# Basic concepts

## Project structure

* src
    * Application.php
    * Controllers
        * HomeController.php
    * Models
        * UserRepository.php
    * ViewModels
        * ViewModel.php
    * Views
        * Home
            * index.php

## Application class

```php
<?php

namespace App;

use Atom\Di\ServiceProviderRegistry;
use Atom\Di\Injector;
use Atom\Router\Route;

class Application extends \Atom\Application
{
    protected function services(ServiceProviderRegistry $providers): void
    {
        // Dispatcher and views are registered by the base application.
        $providers->add(Services::class);
    }

    protected function bootstrap(Injector $injector): void
    {
        Route::get("/", [HomeController::class, "index"]);
    }
}
```

## Routes configuration

```php
<?php

namespace App;

use Atom\Router\Router;
use Atom\Router\Route;

class Routes
{
    public function __invoke(): void
    {
        Route::group("/", function (Router $group) {
            $group->middleware(LogMiddleware::class);

            Route::controller(HomeController::class, function () {
                Route::get("", "index")->name("home");
                Route::get("item", "item");
                Route::get("json", "json");
                Route::get("filter", "index");
            });

            Route::get("validation", [ValidationController::class, "index"]);

            Route::attach(AccountController::class);
        });

        // Build routes from method attributes
        Route::attachTo("/api", ApiController::class);

        Route::get(
            "/api/users-all",
            function (UserRepository $users) {
                return $users->findAll();
            }
        );
    }
}
```

## Controllers

```php
<?php

namespace App\Controllers;

use Atom\Router\MatchedRoute;
use Atom\Router\Attributes\Controller;
use Atom\Router\Attributes\Get;
use Atom\View\ViewInfo;
use App\Models\UserRepository;
use App\Messages\FormPostMessage;

#[Controller("/")]
final class HomeController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Get("")]
    final public function index($id = 0, FormPostMessage $post, MatchedRoute $route)
    {
        return new ViewInfo('home/index', [
            'items' => $this->userRepository->findAll(),
            'post' => $post,
            'route' => $route
        ]);
    }
}
```

## Models

Simple user model
```php
<?php

namespace App\Models;

use Atom\Database\Orm\Attributes\Column;
use Atom\Database\Orm\Attributes\PrimaryKey;
use Atom\Database\Orm\Attributes\Table;
use Atom\Database\Orm\Provider\NowProvider;

#[Table("users")]
final class User
{
    #[PrimaryKey("id")]
    public int $id;

    #[Column("first_name")]
    public string $firstName;

    #[Column("last_name")]
    public string $lastName;

    #[Column("email")]
    public string $email;

    #[Column("created_at", onInsert: NowProvider::class)]
    public DateTimeImmutable $createdAt;

    #[Column("updated_at", onInsert: NowProvider::class, onUpdate: NowProvider::class)]
    public DateTimeImmutable $updatedAt;
}
```
Simple database usage
```php
<?php

namespace App\Models;

use Atom\Database\Db;
use Atom\Database\Sql\Op;

final class UserRepository
{
    public function __construct(private Db $db)
    {
    }

    /**
     * @return list<User>
     */
    public function findAll(): array
    {
        return $this->db
            ->select(User::class)
            ->where("id", Op::gt(2))
            ->where("id", Op::lt(10))
            ->orWhereExp("id = :id", ["id" => 100])
            ->limit(10)
            ->all();
    }
}
```

## Views

- Views\layout.php

```html
<!doctype html>
<html lang="en">
    <body>
        <main role="main" class="container">
            <?= $content ?>
        </main>
    </body>
</html>
```

- Views\Home\index.php

```html
<?php $view->extend("layout"); ?>

<h2>Some items</h2>
<?php if ($items): ?>
<table class="table">
    <?php foreach($items as $item): ?>
    <tr>
        <td><?= $item->id ?></td>
        <td><?= $item->username ?></td>
        <td><?= $item->email ?></td>
        <td>
            <div class="float-right">
                <a class="btn btn-sm btn-primary" href="<?= $baseUrl ?>item">
                    Detail
                </a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
```
