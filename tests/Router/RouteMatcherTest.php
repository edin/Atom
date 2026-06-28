<?php

declare(strict_types=1);

namespace Atom\Tests\Router;

use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\RouteMatcher;
use Atom\Router\Router;
use PHPUnit\Framework\TestCase;

final class RouteMatcherTest extends TestCase
{
    public function testMatcherFindsStaticRoute(): void
    {
        $route = RouteEntry::route("GET", "/health", RouteAction::fromClosure(fn () => "ok"));
        $router = new Router();
        $router->add($route);

        $result = (new RouteMatcher($router))->match("GET", "/health");

        $this->assertTrue($result->isFound());
        $this->assertSame($route, $result->matchedRoute->getRouteEntry());
    }

    public function testMatcherFindsRouteWithParams(): void
    {
        $route = RouteEntry::route("GET", "/users/{id}", RouteAction::fromClosure(fn () => []));
        $router = new Router("/api");
        $router->add($route);

        $result = (new RouteMatcher($router))->match("GET", "/api/users/42", ["tab" => "profile"]);

        $this->assertTrue($result->isFound());
        $this->assertSame(["id" => "42"], $result->matchedRoute->getRouteParams());
        $this->assertSame(["tab" => "profile"], $result->matchedRoute->getQueryParams());
    }

    public function testMatcherHandlesMethodNotAllowed(): void
    {
        $router = new Router();
        $router->add(RouteEntry::route("GET", "/users", RouteAction::fromClosure(fn () => [])));
        $router->add(RouteEntry::route("POST", "/users", RouteAction::fromClosure(fn () => [])));

        $result = (new RouteMatcher($router))->match("DELETE", "/users");

        $this->assertFalse($result->isFound());
        $this->assertTrue($result->isMethodNotAllowed());
        $this->assertSame(["GET", "POST"], $result->allowedMethods);
    }

    public function testMatcherHandlesNotFound(): void
    {
        $router = new Router();
        $router->add(RouteEntry::route("GET", "/users", RouteAction::fromClosure(fn () => [])));

        $result = (new RouteMatcher($router))->match("GET", "/missing");

        $this->assertFalse($result->isFound());
        $this->assertFalse($result->isMethodNotAllowed());
    }

    public function testMatcherHandlesMultipleMethodsOnRoute(): void
    {
        $route = RouteEntry::route(["GET", "POST"], "/users", RouteAction::fromClosure(fn () => []));
        $router = new Router();
        $router->add($route);

        $result = (new RouteMatcher($router))->match("POST", "/users");

        $this->assertTrue($result->isFound());
        $this->assertSame($route, $result->matchedRoute->getRouteEntry());
    }

    public function testMatcherFindsRouteWithWildcardParam(): void
    {
        $route = RouteEntry::route("GET", "/resources/{path*}", RouteAction::fromClosure(fn () => []));
        $router = new Router();
        $router->add($route);

        $result = (new RouteMatcher($router))->match("GET", "/resources/css/app.css");

        $this->assertTrue($result->isFound());
        $this->assertSame(["path" => "css/app.css"], $result->matchedRoute->getRouteParams());
    }
}
