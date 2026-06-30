<?php

declare(strict_types=1);

namespace Atom\Router;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Atom\Router\RouteAction;
use Atom\Router\Attributes\Controller;
use Atom\Router\Attributes\HttpRoute;

final class Route
{
    private static ?Router $router = null;
    /** @var Router[] */
    private static array $routerStack = [];
    /** @var array<int, string|null> */
    private static array $controllerStack = [];

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
        self::$routerStack = [];
        self::$controllerStack = [];
    }

    public static function clearRouter(): void
    {
        self::$router = null;
        self::$routerStack = [];
        self::$controllerStack = [];
    }

    public static function getRouter(): Router
    {
        if (self::$router === null) {
            throw new RuntimeException("No shared router has been configured.");
        }

        return self::$router;
    }

    private static function currentRouter(): Router
    {
        if (count(self::$routerStack) > 0) {
            return self::$routerStack[count(self::$routerStack) - 1];
        }

        return self::getRouter();
    }

    private static function currentController(): ?string
    {
        if (count(self::$controllerStack) > 0) {
            return self::$controllerStack[count(self::$controllerStack) - 1];
        }

        return null;
    }

    public static function addRoute(string|array $method, string $path, mixed $handler = null): RouteEntry
    {
        return self::currentRouter()->add(self::makeEntry($method, $path, $handler));
    }

    private static function makeEntry(string|array $method, string $path, mixed $handler = null): RouteEntry
    {
        if (!($handler instanceof RouteAction)) {
            if ($handler == null) {
                $handler = [self::requireCurrentController(), $path];
            } elseif (is_string($handler)) {
                $handler = [self::requireCurrentController(), $handler];
            }

            $handler = RouteAction::from($handler);
        }

        return RouteEntry::create($method, $path, $handler);
    }

    private static function requireCurrentController(): string
    {
        $controller = self::currentController();

        if ($controller === null) {
            throw new RuntimeException("No route controller has been configured for controller action shorthand.");
        }

        return $controller;
    }

    private static function joinPaths(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix, " /");
        $path = ltrim($path, " /");

        if ($path !== "") {
            $path = "/" . $path;
        }

        $result = $prefix . $path;

        return $result === "" ? "/" : $result;
    }

    public static function getOrPost(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute(["GET", "POST"], $path, $handler);
    }

    public static function get(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("GET", $path, $handler);
    }

    public static function post(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("POST", $path, $handler);
    }

    public static function put(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("PUT", $path, $handler);
    }

    public static function patch(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("PATCH", $path, $handler);
    }

    public static function delete(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("DELETE", $path, $handler);
    }

    public static function head(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("HEAD", $path, $handler);
    }

    public static function options(string $path, mixed $handler = null): RouteEntry
    {
        return self::addRoute("OPTIONS", $path, $handler);
    }

    public static function group(
        string $path = "",
        ?Closure $routeBuilder = null
    ): Router {
        $parent = self::currentRouter();
        $group = new Router($path);
        $parent->add($group);

        if ($routeBuilder !== null) {
            self::$routerStack[] = $group;
            self::$controllerStack[] = self::currentController();
            try {
                $routeBuilder($group);
            } finally {
                array_pop(self::$routerStack);
                array_pop(self::$controllerStack);
            }
        }

        return $group;
    }

    public static function controller(string $controller, ?Closure $routeBuilder = null): Router
    {
        $router = self::currentRouter();

        if ($routeBuilder !== null) {
            self::$routerStack[] = $router;
            self::$controllerStack[] = $controller;
            try {
                $routeBuilder($router);
            } finally {
                array_pop(self::$routerStack);
                array_pop(self::$controllerStack);
            }
        }

        return $router;
    }

    public static function attach(string $controller): Router
    {
        $router = self::currentRouter();
        self::attachController($router, $controller);
        return $router;
    }

    public static function attachTo(string $path, string $controller): Router
    {
        $parent = self::currentRouter();
        $router = new Router($path);
        $parent->add($router);
        self::attachController($router, $controller);

        return $router;
    }

    private static function attachController(Router $router, string $controller): void
    {
        $class = new ReflectionClass($controller);
        $controllerPath = self::getControllerPath($class);

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(HttpRoute::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $route = $attribute->newInstance();
                $router->add(RouteEntry::create(
                    $route->method,
                    self::joinPaths($controllerPath, $route->path),
                    [$controller, $method->getName()]
                ));
            }
        }
    }

    private static function getControllerPath(ReflectionClass $class): string
    {
        $attributes = $class->getAttributes(Controller::class);

        if (count($attributes) === 0) {
            return "";
        }

        return $attributes[0]->newInstance()->path;
    }
}
