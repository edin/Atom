# Middlewares

[Atom Framework](Index.md)

Midlewares are based on [PSR15 Middlewares](https://www.php-fig.org/psr/psr-15/)

## Example

* Example of middleware that adds a response header

```php
<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

final class LogPathMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler) : ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $result =  $handler->handle($request);
        return $result->withAddedHeader("X-Path", $path);
    }
}
```

* Middleware can be attached to the group or the specific route

```php
<?php

namespace App;

class Routes
{
    public function configure(Router $router)
    {
        $router->addGroup("/", function (Router $group) {
            $group->addMiddleware(LogPathMiddleware::class);

            $group->get("hello", fn() => "Hello");

            $group->get("world", fn() => "World")
                  ->addMiddleware(SomeMiddleware::class);
        });
    }
}
```

* Register routes whitin application 

```php
<?php

namespace App;

class Application extends \Atom\Application
{
    public function configure()
    {
        $this->use(Routes::class);
    }
}
```