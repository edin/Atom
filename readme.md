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
- PSR-7 HTTP Message interfaces
- PSR-15 Middleware support

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

use Atom\View\ViewServices;
use Atom\Dispatcher\DispatcherServices;

class Application extends \Atom\Application
{
    public function configure()
    {
        // Register some service providers
        $this->use(DispatcherServices::class);
        $this->use(ViewServices::class);
        $this->use(Routes::class);
        $this->use(TypeFactory::class);
        $this->use(Services::class);
    }
}
```

## Routes configuration

```php
<?php

namespace App;

use Atom\Router\Router;
use Atom\Router\RouteBuilder;

class Routes
{
    public function configure(Router $router)
    {
        $router->group("/", function (Router $group) {
            $group->middleware(LogMiddleware::class);
            $group->setController(HomeController::class);

            $group->get("", "index")->withName("home");
            $group->get("item", "item");
            $group->get("json", "json");
            $group->get("filter", "index");
            $group->get("validation", ValidationController::class, "index");

            $group->attach(
                RouteBuilder::fromController(AccountController::class)
            );
        });

        //Build routes from simple annotation definitions
        $router->attachTo(
            "/api",
            RouteBuilder::fromController(ApiController::class)
        );

        $router->get(
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

use Atom\Router\Route;
use Atom\View\ViewInfo;
use App\Models\UserRepository;
use App\Messages\FormPostMessage;

final class HomeController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    final public function index($id = 0, FormPostMessage $post, Route $route)
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

use Atom\Database\Mapping\Mapping;
use Atom\Database\Mapping\DateTimeConverter;
use Atom\Database\Mapping\CurrentDateTimeProvider;

final class User
{
    public int $id;
    public string $first_name;
    public string $last_name;
    public string $email;
    public DateTimeImmutable $created_at;
    public DateTimeImmutable $updated_at;    

    public function getMapping(): Mapping
    {
        return Mapping::create(function (Mapping $map) {
            $map->table("users");
            $map->setEntity(User::class);
            $map->setRepository(UserRepository::class)
            $map->property("id")->field("id")->primaryKey()->int();
            $map->property("first_name")->field("first_name")->string(50);
            $map->property("last_name")->field("last_name")->string(50);
            $map->property("email")->field("email")->string(100);

            $map->property("password_hash")
                ->field("password_hash")
                ->string(255)
                ->excludeInSelect();

            $map->property("created_at")->field("created_at")->date()
                ->excludeInUpdate()
                ->withValueProvider(CurrentDateTimeProvider::class)
                ->withConverter(DateTimeConverter::class);

            $map->property("updated_at")->field("updated_at")->date()
                ->withValueProvider(CurrentDateTimeProvider::class)
                ->withConverter(DateTimeConverter::class);
        });
    }    
}
```
Simple User repository
```php
<?php

namespace App\Models;

use Atom\Database\Repository;
use Atom\Database\Query\Query;
use Atom\Database\Query\Operator;
use Atom\Database\EntityCollection;

class UserRepository extends Repository
{
    protected string $entityType = User::class;
    
    public function findAll(): EntityCollection
    {
        return $this->query()
                ->where("id", Operator::greater(2))
                ->where("id", Operator::less(10))
                ->orWhere("id = :id", 100)
                ->limit(10)
                ->findAll();
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