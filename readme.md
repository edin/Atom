# Atom Framework

Simple PHP Framework

```
    git clone https://github.com/edin/AtomApp.git
    composer update
    php -S localhost:3000
    http://localhost:3000/public
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
        $router->addGroup("/", function (Router $group) {
            $group->addMiddleware(LogMiddleware::class);
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

final class User
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;

    public static function from($id, $first_name, $last_name, $email)
    {
        $user = new static();
        $user->id = $id;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        return $user;
    }
}
```
Simple User repository
```php
<?php

namespace App\Models;

use Atom\Database\Query\Query;
use Atom\Collections\Collection;
use Atom\Database\Query\Operator;

class UserRepository
{
    public function findAll()
    {
        $query = Query::select("users u")
                ->where("u.id", Operator::greater(2))
                ->where("u.id", Operator::less(10))
                ->orWhere("u.id = :id", 100)
                ->limit(10);

        $items = $query->queryAll();

        //Currently there is no object hydration support but
        //hopefully there will be simple hydrator so following wont be needed

        return Collection::from($items)->map(function ($item) {
            return User::from(
                $item['id'],
                $item['first_name'],
                $item['last_name'],
                $item['email']
            );
        });
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