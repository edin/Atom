<?php

declare(strict_types=1);

namespace Atom\Tests\Router;

use Atom\Router\Route;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\Router\RouteAction;
use Atom\Router\Attributes\Controller;
use Atom\Router\Attributes\Get;
use Atom\Router\Attributes\Post;
use PHPUnit\Framework\TestCase;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Http\MiddlewareInterface;
use Atom\Http\RequestHandlerInterface;
use RuntimeException;

final class RouterTest extends TestCase
{
    protected function tearDown(): void
    {
        Route::clearRouter();
    }

    public function testStaticRouteFacadeRegistersRouteOnSharedRouter(): void
    {
        $router = new Router();
        Route::setRouter($router);

        $route = Route::get("/users", function () {
            return "users";
        });

        $routes = $router->getAllRoutes();

        $this->assertInstanceOf(RouteEntry::class, $route);
        $this->assertSame($route, $routes[0]);
        $this->assertSame("GET", $route->getMethod());
        $this->assertSame("/users", $route->getFullPath());
        $this->assertTrue($route->isClosure());
    }

    public function testStaticRouteFacadeRegistersHeadRoute(): void
    {
        $router = new Router();
        Route::setRouter($router);

        $route = Route::head("/users", function () {
            return "";
        });

        $this->assertSame("HEAD", $route->getMethod());
        $this->assertSame([$route], $router->getAllRoutes());
    }

    public function testRouterAddsRouteEntry(): void
    {
        $router = new Router();
        $entry = RouteEntry::create(
            "GET",
            "/health",
            RouteAction::closure(function () {
                return "ok";
            })
        );

        $result = $router->add($entry);

        $this->assertSame($entry, $result);
        $this->assertSame([$entry], $router->getRoutes());
    }

    public function testRouterCanBeConstructedWithPath(): void
    {
        $router = new Router("/api");

        $this->assertSame("/api", $router->getFullPath());
    }

    public function testRouterMountsChildRouterAsRouteEntry(): void
    {
        $root = new Router();
        $api = new Router("/api");

        $api->add(RouteEntry::create(
            "GET",
            "/users",
            RouteAction::closure(function () {
                return [];
            })
        ));

        $mount = $root->add($api);
        $routes = $root->getAllRoutes();

        $this->assertSame($api, $mount);
        $this->assertCount(1, $routes);
        $this->assertSame("/api/users", $routes[0]->getFullPath());
    }

    public function testStaticRouteFacadeRegistersRoutesInsideGroup(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::group("/admin", function () {
            Route::get("users", function () {
                return "admin users";
            });
        });

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame("/admin/users", $routes[0]->getFullPath());
    }

    public function testStaticRouteFacadeRestoresParentRouterAfterGroup(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::group("/admin", function () {
            Route::get("users", function () {
                return "admin users";
            });
        });

        Route::get("/health", function () {
            return "ok";
        });

        $routes = $router->getAllRoutes();

        $this->assertSame("/admin/users", $routes[0]->getFullPath());
        $this->assertSame("/health", $routes[1]->getFullPath());
    }

    public function testNestedGroupsComposeFullPath(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::group("/api", function () {
            Route::group("v1", function () {
                Route::get("users", function () {
                    return [];
                });
            });
        });

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame("/api/v1/users", $routes[0]->getFullPath());
    }

    public function testStaticRouteFacadeUsesControllerGroupForActionShorthand(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::controller(self::class, function () {
            Route::get("/users", "users");
        });

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame(self::class, $routes[0]->getController());
        $this->assertSame("users", $routes[0]->getMethodName());
        $this->assertSame("/users", $routes[0]->getFullPath());
    }

    public function testStaticRouteFacadeUsesPathAndControllerGroupForActionShorthand(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::group("/admin", function () {
            Route::controller(self::class, function () {
                Route::get("users", "users");
            });
        });

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame(self::class, $routes[0]->getController());
        $this->assertSame("users", $routes[0]->getMethodName());
        $this->assertSame("/admin/users", $routes[0]->getFullPath());
    }

    public function testNestedGroupsInheritController(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::controller(self::class, function () {
            Route::group("/admin", function () {
                Route::get("users", "users");
            });
        });

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame(self::class, $routes[0]->getController());
        $this->assertSame("/admin/users", $routes[0]->getFullPath());
    }

    public function testStaticRouteFacadeRequiresControllerForActionShorthand(): void
    {
        $router = new Router();
        Route::setRouter($router);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No route controller has been configured for controller action shorthand.");

        Route::get("/users", "users");
    }

    public function testStaticRouteFacadeAttachesAnnotatedControllerToCurrentRouter(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::attach(AnnotatedTestController::class);

        $routes = $router->getAllRoutes();

        $this->assertCount(2, $routes);
        $this->assertSame("/health", $routes[0]->getFullPath());
        $this->assertSame("GET", $routes[0]->getMethod());
        $this->assertSame(AnnotatedTestController::class, $routes[0]->getController());
        $this->assertSame("health", $routes[0]->getMethodName());
        $this->assertSame("/users", $routes[1]->getFullPath());
        $this->assertSame("POST", $routes[1]->getMethod());
    }

    public function testStaticRouteFacadeAttachesAnnotatedControllerToMountedRouter(): void
    {
        $router = new Router();
        Route::setRouter($router);

        $mounted = Route::attachTo("/api", AnnotatedTestController::class);

        $routes = $router->getAllRoutes();

        $this->assertSame("/api", $mounted->getFullPath());
        $this->assertCount(2, $routes);
        $this->assertSame("/api/health", $routes[0]->getFullPath());
        $this->assertSame("/api/users", $routes[1]->getFullPath());
    }

    public function testStaticRouteFacadeUsesControllerAttributePath(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::attach(PrefixedAnnotatedTestController::class);

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame("/api/users", $routes[0]->getFullPath());
    }

    public function testStaticRouteFacadeCombinesAttachPathWithControllerAttributePath(): void
    {
        $router = new Router();
        Route::setRouter($router);

        $mounted = Route::attachTo("/v1", PrefixedAnnotatedTestController::class);

        $routes = $router->getAllRoutes();

        $this->assertSame("/v1", $mounted->getFullPath());
        $this->assertCount(1, $routes);
        $this->assertSame("/v1/api/users", $routes[0]->getFullPath());
    }

    public function testControllerAttributePathHandlesEmptyMethodPath(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::attach(EmptyMethodPathAnnotatedTestController::class);

        $routes = $router->getAllRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame("/api", $routes[0]->getFullPath());
    }

    public function testRouteEntryMergesRouterAndEntryMiddlewares(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::group("/admin", function (Router $router) {
            $router->middleware("auth");
            Route::get("users", function () {
                return "admin users";
            })->middleware("log");
        });

        $routes = $router->getAllRoutes();

        $this->assertSame(["auth", "log"], $routes[0]->getMiddlewares());
        $this->assertSame(["log"], $routes[0]->getOwnMiddlewares());
    }

    public function testChildRouterUsesLateParentMiddlewares(): void
    {
        $router = new Router();
        Route::setRouter($router);

        Route::group("/admin", function () {
            Route::get("users", function () {
                return "admin users";
            })->middleware("log");
        });

        $router->middleware("auth");

        $routes = $router->getAllRoutes();

        $this->assertSame(["auth", "log"], $routes[0]->getMiddlewares());
    }

    public function testMiddlewareAcceptsClassStringAndInstance(): void
    {
        $router = new Router();
        Route::setRouter($router);
        $middleware = new RouterTestMiddleware();

        $router->middleware(RouterTestMiddleware::class);
        Route::get("/users", function () {
            return [];
        })->middleware($middleware);

        $routes = $router->getAllRoutes();

        $this->assertSame([RouterTestMiddleware::class, $middleware], $routes[0]->getMiddlewares());
    }

    public function testRouteEntryKeepsMetadata(): void
    {
        $router = new Router();
        Route::setRouter($router);
        $metadata = new \stdClass();

        $route = Route::get("/users", function () {
            return "users";
        })->metadata($metadata);

        $this->assertSame([$metadata], $route->getMetadata());
        $this->assertSame($metadata, $route->getMetadataOfType(\stdClass::class));
        $this->assertSame([$metadata], $route->getMetadataArrayOfType(\stdClass::class));
    }

    public function testStaticRouteFacadeRequiresSharedRouter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No shared router has been configured.");

        Route::get("/users", function () {
            return "users";
        });
    }
}

final class AnnotatedTestController
{
    #[Get("/health")]
    public function health(): string
    {
        return "ok";
    }

    #[Post("/users")]
    public function create(): array
    {
        return [];
    }
}

#[Controller("/api")]
final class PrefixedAnnotatedTestController
{
    #[Get("/users")]
    public function users(): array
    {
        return [];
    }
}

#[Controller("/api")]
final class EmptyMethodPathAnnotatedTestController
{
    #[Get("")]
    public function index(): array
    {
        return [];
    }
}

final class RouterTestMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request);
    }
}
