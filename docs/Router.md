# Router

[Atom Framework](Index.md)

The router is a small route tree. It stores route entries and child routers.

## Class Synopsis

```php
class Router
{
    public function __construct(string $path = "");
    public function getAllRoutes(): array;
    public function getItems(): array;
    public function getRoutes(): array;
    public function add(RouteEntry|Router $item): RouteEntry|Router;

    public function getPath(): string;
    public function getFullPath(): string;

    public function middleware(string|MiddlewareInterface $middleware): self;
    public function getOwnMiddlewares(): array;
    public function getMiddlewares(): array;
}

final class RouteEntry
{
    public static function route($method, string $path, RouteAction $routeAction): self;

    public function getRouter(): ?Router;

    public function name(string $name): self;
    public function title(string $title): self;
    public function description(string $description): self;
    public function middleware(string|MiddlewareInterface $middleware): self;
    public function metadata(object $instance): self;

    public function getName(): ?string;
    public function getTitle(): ?string;
    public function getDescription(): ?string;
    public function getPath(): string;
    public function getFullPath(): string;
    public function getOwnMiddlewares(): array;
    public function getMiddlewares(): array;
    public function getMetadata(): array;
    public function getMetadataOfType(string $typeName): ?object;
    public function getMetadataArrayOfType(string $typeName): array;

    public function toController(string $controllerType, string $actionName): self;
    public function toClosure(callable $closure): self;
    public function getMethod(): string|array;
    public function getMethodList(): string;
    public function getRouteAction(): RouteAction;
    public function getController(): ?string;
    public function getMethodName(): ?string;
    public function getClosure(): ?callable;
    public function isClosure(): bool;
}

final readonly class MatchedRoute
{
    public function __construct(RouteEntry $route, array $routeParams = [], array $queryParams = []);
    public function getRouteEntry(): RouteEntry;
    public function getQueryParams(): array;
    public function getRouteParams(): array;
    public function getParams(): array;
    public function getMiddlewares(): array;
    public function getRouteAction(): RouteAction;
}

final class RouteMatcher
{
    public function __construct(Router $router);
    public function match(string $method, string $path, array $queryParams = []): RouteMatchResult;
}

final readonly class RouteMatchResult
{
    public ?MatchedRoute $matchedRoute;
    public array $allowedMethods;

    public function isFound(): bool;
    public function isMethodNotAllowed(): bool;
}

final class Route
{
    public static function setRouter(Router $router): void;
    public static function clearRouter(): void;
    public static function getRouter(): Router;

    public static function addRoute($method, string $path, $handler = null): RouteEntry;
    public static function getOrPost(string $path, $handler = null): RouteEntry;
    public static function get(string $path, $handler = null): RouteEntry;
    public static function post(string $path, $handler = null): RouteEntry;
    public static function put(string $path, $handler = null): RouteEntry;
    public static function patch(string $path, $handler = null): RouteEntry;
    public static function delete(string $path, $handler = null): RouteEntry;
    public static function head(string $path, $handler = null): RouteEntry;
    public static function options(string $path, $handler = null): RouteEntry;

    public static function group(string $path = "", ?Closure $routeBuilder = null): Router;
    public static function controller(string $controller, ?Closure $routeBuilder = null): Router;
    public static function attach(string $controller): Router;
    public static function attachTo(string $path, string $controller): Router;
}
```

## Examples

Register routes through the shared facade:

```php
Route::group("/admin", function (Router $router) {
    $router->middleware(AuthMiddleware::class);

    Route::controller(AdminController::class, function () {
        Route::get("users", "users")->name("admin.users");
        Route::post("users", "create");
    });
});
```

Use a controller-only group when the routes do not need a path prefix:

```php
Route::controller(AdminController::class, function () {
    Route::get("/dashboard", "dashboard");
});
```

Mount routes from controller method attributes:

```php
use Atom\Router\Attributes\Get;
use Atom\Router\Attributes\Post;
use Atom\Router\Attributes\Controller;

#[Controller("/api")]
final class ApiController
{
    #[Get("/users")]
    public function users(): array
    {
        return [];
    }

    #[Post("/users")]
    public function create(): array
    {
        return [];
    }
}

Route::attach(ApiController::class);
Route::attachTo("/v1", ApiController::class);
```

Add an entry directly to a router:

```php
$router->add(RouteEntry::route(
    "GET",
    "/health",
    RouteAction::fromClosure(fn () => "ok")
));
```

## Route Metadata

Route entries can carry small metadata objects:

```php
$entry = RouteEntry::route(
    "GET",
    "/dashboard",
    RouteAction::fromMethod(DashboardController::class, "index")
)->metadata(new RequiresRole("admin"));
```

Metadata is available from the matched route:

```php
$metadata = $matchedRoute
    ->getRouteEntry()
    ->getMetadataOfType(RequiresRole::class);
```

Pages use this internally. `Page::registerPages()` registers a normal route action pointing at `PageRouteHandler::render`, then stores the target page class as route metadata. This keeps page routes inspectable and easier to cache or serialize later.

Metadata should stay simple: readonly DTOs with scalar values, arrays, enums, or class names. Avoid closures or service instances if route caching is a future goal.
