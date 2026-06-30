<?php

declare(strict_types=1);

namespace Atom\Tests\Router;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Router\Action;
use Atom\Router\MatchedRoute;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use PHPUnit\Framework\TestCase;

final class ActionTest extends TestCase
{
    public function testActionInvokesClosureWithRouteParameters(): void
    {
        $route = RouteEntry::create(
            "GET",
            "/users/{id}",
            RouteAction::closure(function (string $id) {
                return "user-" . $id;
            })
        );

        $action = new Action(new Injector(), new MatchedRoute($route, ["id" => "42"]));

        $this->assertSame("user-42", $action->execute());
    }

    public function testMatchedRouteKeepsRouteDefinitionSeparateFromParams(): void
    {
        $route = RouteEntry::create(
            "GET",
            "/users/{id}",
            RouteAction::closure(fn () => null)
        );

        $matchedRoute = new MatchedRoute($route, ["id" => "42"], ["tab" => "profile"]);

        $this->assertSame($route, $matchedRoute->getRouteEntry());
        $this->assertSame(["id" => "42"], $matchedRoute->getRouteParams());
        $this->assertSame(["tab" => "profile"], $matchedRoute->getQueryParams());
        $this->assertSame(["tab" => "profile", "id" => "42"], $matchedRoute->getParams());
    }

    public function testActionInvokesControllerMethodWithRouteParameters(): void
    {
        $route = RouteEntry::create(
            "GET",
            "/users/{id}",
            RouteAction::method(ActionTestController::class, "show")
        );

        $action = new Action(new Injector(), new MatchedRoute($route, ["id" => "42"]));

        $this->assertSame("user-42", $action->execute());
    }

    public function testActionInvokesControllerInvokeMethodByDefault(): void
    {
        $route = RouteEntry::create(
            "GET",
            "/users/{id}",
            RouteAction::method(ActionInvokeController::class)
        );

        $action = new Action(new Injector(), new MatchedRoute($route, ["id" => "42"]));

        $this->assertSame("invoked-42", $action->execute());
    }

    public function testRouteEntryCreateAcceptsCallableAndArrayAndInvokableClass(): void
    {
        $closure = RouteEntry::get("/users/{id}", fn(string $id): string => "closure-" . $id);
        $method = RouteEntry::get("/users/{id}", [ActionTestController::class, "show"]);
        $invoke = RouteEntry::get("/users/{id}", ActionInvokeController::class);

        $this->assertSame("closure-42", (new Action(new Injector(), new MatchedRoute($closure, ["id" => "42"])))->execute());
        $this->assertSame("user-42", (new Action(new Injector(), new MatchedRoute($method, ["id" => "42"])))->execute());
        $this->assertSame("invoked-42", (new Action(new Injector(), new MatchedRoute($invoke, ["id" => "42"])))->execute());
    }

    public function testRouteEntryCanReplaceRouteAction(): void
    {
        $route = RouteEntry::create(
            "GET",
            "/users/{id}",
            RouteAction::closure(fn () => "old")
        )->action(RouteAction::method(ActionTestController::class, "show"));

        $action = new Action(new Injector(), new MatchedRoute($route, ["id" => "42"]));

        $this->assertSame("user-42", $action->execute());
    }

    public function testActionReusesScopedDependenciesAcrossControllerAndMethod(): void
    {
        $route = RouteEntry::create(
            "GET",
            "/users/{id}",
            RouteAction::method(ActionScopedController::class, "show")
        );

        $bindings = Bindings::create();
        $bindings->bind(ActionScopedDependency::class)
            ->toSelf()
            ->scoped();

        $action = new Action(new Injector($bindings), new MatchedRoute($route, ["id" => "42"]));

        $this->assertSame("same", $action->execute());
    }
}

final class ActionTestController
{
    public function show(string $id): string
    {
        return "user-" . $id;
    }
}

final class ActionInvokeController
{
    public function __invoke(string $id): string
    {
        return "invoked-" . $id;
    }
}

final readonly class ActionScopedController
{
    public function __construct(private ActionScopedDependency $dependency)
    {
    }

    public function show(ActionScopedDependency $dependency): string
    {
        return $this->dependency === $dependency ? "same" : "different";
    }
}

final class ActionScopedDependency
{
}
