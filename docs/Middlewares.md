# Middlewares

[Atom Framework](Index.md)

Middlewares use Atom's small request/response wrappers and a simple request-handler pipeline.

## Global middleware

Global middleware runs before route matching and can short-circuit the request. Register it from the application:

```php
use Atom\Http\MiddlewareRegistry;

protected function middlewares(MiddlewareRegistry $middlewares): void
{
    $middlewares->add(TrustedProxyMiddleware::class);
    $middlewares->add(TrustedHostMiddleware::class);
    $middlewares->add(RequestIdMiddleware::class);
    $middlewares->add(RequestBodyLimitMiddleware::class);
    $middlewares->add(CorsMiddleware::class);
    $middlewares->add(RateLimitMiddleware::class);
}
```

Order is significant. Route and router-group middleware continue to run after a route has matched.

## Example

* Example of middleware that adds a response header

```php
<?php

namespace App\Middlewares;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final class LogPathMiddleware implements MiddlewareInterface
{
    public function process(
        Request $request,
        RequestHandlerInterface $handler
    ): Response
    {
        $path = $request->getPath();
        $response = $handler->handle($request);
        return $response->addHeader("X-Path", $path);
    }
}
```

* Middleware can be attached to the group or the specific route

```php
<?php

namespace App;

use Atom\Router\Route;
use Atom\Router\Router;

class Application extends \Atom\Application
{
    protected function bootstrap(\Atom\Di\Injector $injector): void
    {
        Route::group("/", function (Router $group) {
            $group->middleware(LogPathMiddleware::class);

            Route::get("hello", fn() => "Hello");

            Route::get("world", fn() => "World")
                ->middleware(SomeMiddleware::class);
        });
    }
}
```

* Register middleware routes within application 

```php
<?php

namespace App;

use Atom\Router\Route;

class Application extends \Atom\Application
{
    protected function bootstrap(\Atom\Di\Injector $injector): void
    {
        Route::get("hello", fn() => "Hello")
            ->middleware(LogPathMiddleware::class);
    }
}
```
