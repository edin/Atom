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

class Application extends \Atom\Application
{
    public function configure()
    {
        $this->use(DispatcherServices::class);
        $this->use(ViewServices::class);
        $this->use(Routes::class);
    }
}
```

## Routes configuration

```php
<?php

namespace App;

class Routes
{
    public function configureServices(Container $di)
    {
        $di->UserRepository = \App\Models\UserRepository::class;
        $di->HomeController = \App\Controllers\HomeController::class;
    }

    public function configure(Router $router)
    {
        $router->addGroup("/", function(RouteGroup $group) {
            $group->addRoute("GET", "/", "HomeController@index");
        });
    }
}
```


## Controllers

```php
<?php

namespace App\Controllers;

use App\Models\UserRepository;
use Atom\View\ViewInfo;

final class HomeController
{
    // Parameters are resolved from container or from route parameters
    public function index($id = 0, UserRepository $repository)
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