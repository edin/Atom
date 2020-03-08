# Atom Framework

Elementar PHP framework delivered within project. Clone and custimize.

```
    git clone https://github.com/edin/Atom.git
    composer install
    php -S localhost:3000
    http://localhost:3000/public
```

# Project goals

- Simple php framework for building rest apis and simple apps
- Simple routing
- Simple dependency injection container
- PSR-7 HTTP Message interfaces
- PSR-15 Middleware support
- Simple php based templates

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
use App\Models\UserRepository;

class Application extends \Atom\Application
{
    public function registerRoutes()
    {
        $router = $this->getRouter();
        $group = $router->addGroup("/");
        $group->addRoute("GET", "/", "HomeController@index");
    }

    public function registerServices()
    {
        $di = $this->getContainer();

        $di->View = function ($di) {
            $view = new \Atom\View\View($di);
            $view->setViewsDir(dirname(__FILE__) . "/Views");
            $view->setEngines([
                ".php" => "ViewEngine",
            ]);
            return $view;
        };

        $di->UserRepository = function ($di) {
            return new \App\Models\UserRepository();
        };

        $di->HomeController = \App\Controllers\HomeController::class;
    }

    public function resolveController($name)
    {
        return $this->getContainer()->get($name);
    }
}
```

## Controllers

```php
<?php

namespace App\Controllers;

use App\Application;
use App\Models\UserRepository;
use Atom\View\ViewInfo;

final class HomeController
{
    // Public fields are resolved by DI using field name
    // e.g. $container->get("UserRepository")
    public $UserRepository;
    public $View;
    public $Response;
    public $Request;
    public $Container;

    // Parameters are resolved by type name or by name
    public function index($id = 0, UserRepository $repository, Application $app)
    {
        return new ViewInfo('home/index', ['items' => $repository->findAll()]);
    }
}
```


## Models

```php
<?php

namespace App\Models;

class UserRepository
{
    public function findAll()
    {
        return [
            User::from(1, "user", "user@mail.com"),
        ];
    }
}
```

## Views

- Views\layout.php

```html
<!doctype html>
<html lang="en">
    <head>
    </head>

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
                <a class="btn btn-sm btn-primary" href="<?= $baseUrl ?>item">Detail</a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
```