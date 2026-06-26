<?php

declare(strict_types=1);

namespace Atom\Router;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use RuntimeException;

final class Action
{
    private ?object $controller = null;
    private ReflectionFunctionAbstract $handler;
    /** @var array<int, mixed> */
    private array $actionArguments = [];

    public function __construct(
        private Injector $injector,
        private MatchedRoute $route,
        private InjectionContext $context = new InjectionContext()
    ) {
        $this->handler = $this->reflectHandler($route->getRouteAction());
    }

    private function reflectHandler(RouteAction $routeAction): ReflectionFunctionAbstract
    {
        if ($routeAction->isClosure()) {
            return new ReflectionFunction($routeAction->closure);
        }

        return $this->resolveControllerMethod($routeAction);
    }

    private function resolveControllerMethod(RouteAction $routeAction): ReflectionMethod
    {
        $controllerType = $routeAction->controllerType;
        $methodName = $routeAction->methodName;

        if ($controllerType === null || $methodName === null) {
            throw new RuntimeException("Controller action must define controller type and method name.");
        }

        $reflection = new ReflectionClass($controllerType);
        if (!$reflection->hasMethod($methodName)) {
            throw new RuntimeException("Controller {$reflection->getName()} does not have method {$methodName}.");
        }

        return $reflection->getMethod($methodName);
    }

    public function getRoute(): MatchedRoute
    {
        return $this->route;
    }

    public function getController(): ?object
    {
        if ($this->controller === null && $this->route->getRouteAction()->isControllerMethod()) {
            $controllerType = $this->route->getRouteAction()->controllerType;
            if ($controllerType !== null) {
                $this->controller = $this->injector->instantiate($controllerType, $this->route->getParams(), $this->context);
            }
        }

        return $this->controller;
    }

    public function getHandler(): ReflectionFunctionAbstract
    {
        return $this->handler;
    }

    /**
     * @return array<int, mixed>
     */
    public function getActionArguments(): array
    {
        return $this->actionArguments;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function setActionArguments(array $arguments): void
    {
        $this->actionArguments = $arguments;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    public function getConstructorArguments(): array
    {
        return [];
    }

    public function execute(?array $arguments = null): mixed
    {
        $arguments ??= $this->actionArguments !== [] ? $this->actionArguments : $this->route->getParams();

        if ($this->handler instanceof ReflectionMethod) {
            return $this->injector->invoke([$this->getController(), $this->handler->getName()], $arguments, $this->context);
        }

        $closure = $this->route->getRouteAction()->closure;
        if ($closure === null) {
            throw new RuntimeException("Closure action must define a callable handler.");
        }

        return $this->injector->invoke($closure, $arguments, $this->context);
    }
}
