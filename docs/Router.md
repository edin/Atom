# Router

[Atom Framework](Index.md)


Class synopsis

```php
class Router
{
    use RouteTrait;
    public static function fromGroupAndPath(Router $router, string $path): Router;
    public function getAllRoutes(): array;
    public function addGroup(string $path = "", ?Closure $routeBuilder = null): self;
    public function controller(string $controller, Closure $routeBuilder);
    public function setController(string $controller);
    public function getController(): ?string;
    public function getGroups(): array;
    public function getRoutes(): array;
    public function addRoute($method, string $path, $controller, $action = null): Route;
    public function getOrPost(string $path, $controller, $action = null): Route;
    public function get(string $path, $controller, $action = null): Route;
    public function post(string $path, $controller, $action = null): Route;
    public function put(string $path, $controller, $action = null): Route;
    public function patch(string $path, $controller, $action = null): Route;
    public function delete(string $path, $controller, $action = null): Route;
    public function options(string $path, $controller, $action = null): Route;
    public function attach(IRouteBuilder $builder): self;
    public function attachTo(string $path, IRouteBuilder $builder): self;
}


trait RouteTrait
{
    public function withName(string $name): self;
    public function withTitle(string $title): self;
    public function withDescription(string $description): self;
    public function getName(): ?string;
    public function getTitle(): ?string;
    public function getDescription(): ?string;
    public function getPath(): string;
    public function setPath(string $path): void;
    public function getFullPath(): string;
    public function getGroup(): ?Router;
    public function setGroup(Router $group): void;
    public function addMiddleware($middleware): self;
    public function addMetadata(object $instance): self;
    public function getMetadata();
    public function getMetadataOfType(string $typeName);
    public function getMetadataArrayOfType(string $typeName);
    public function getOwnMiddlewares(): array;
    public function getMiddlewares(): array;
}

final class Route
{
    use RouteTrait;
    public function __construct(Router $group, $method, string $path, ActionHandler $actionHandler);
    public function setRouteParams(array $params): void;
    public function setQueryParams(array $params): void;
    public function getQueryParams(): array;
    public function getParams(): array;
    public function getRouteParams(): array;
    public function toController(string $controllerType, string $actionName): self;
    public function toClosure(callable $closure): self;
    public function getMethod();
    public function getActionHandler();
    public function getController(): ?string;
    public function getMethodName(): ?string;
    public function getClosure(): ?callable;
    public function isClosure(): bool;
}

final class ActionHandler
{
    public function __construct(?string $controller, ?string $methodName, ?callable $closure);
    public static function from($controller, ?string $method = null): self;
    public static function fromMethod(string $controller, string $methodName): self;
    public static function fromClosure(callable $closure): self;
    public function setController(string $controller, string $methodName): void;
    public function getController(): ?string;
    public function getMethodName(): ?string;
    public function setClosure(callable $closure): void;
    public function getClosure(): ?callable;
    public function isClosure(): bool;
}

interface IRouteBuilder
{
    public function build(Router $router);
}

```

## Examples

* Grouping routes

```php
<?php

$router->addGroup("/", function (Router $group) {
    // Add middleware to group 
    $group->addMiddleware(LogMiddleware::class);
    // Set default controller for successive calls
    $group->setController(UserController::class); 
    // Bind routes to actions
    $group->get("", "findAll");
    $group->get("{id}", "findById");
    $group->post("{id}", "create");
    $group->put("{id}", "update");
    $group->delete("{id}", "delete");
});
```

* Attaching routes from doc blocks 

```php
$router->attach(RouteBuilder::fromController(SomeController::class));
```

* Attach closure to route

```php
$router->get("/api/users-all", function (UserRepository $users) {
    return $users->findAll();
});
```

To add routes to an application define service provider like Routes and register service provider in application class.

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

class Routes
{
    public function configure(Router $router) {
        // Configure router here
    }
}
```