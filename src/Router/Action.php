<?php

namespace Atom\Router;

use Atom\Container\Container;
use Atom\Container\ResolutionContext;
use Atom\Container\Resolver\ClassResolver;
use Atom\Router\Route;
use ReflectionClass;
use ReflectionFunction;
use RuntimeException;

final class Action
{
    private $container;
    private $route;
    private $controller;
    private $handler;
    private $actionParameters = [];
    private $properties = [];

    public function __construct(Container $container, Route $route)
    {
        $this->container = $container;
        $this->route = $route;

        $actionHandler = $this->route->getActionHandler();

        if ($actionHandler->isClosure()) {
            $this->handler = new ReflectionFunction($actionHandler->getClosure());
        } else {
            $controllerType = $actionHandler->getController();
            $methodName = $actionHandler->getMethodName();
            $this->ensureMethodExists($controllerType, $methodName);

            $resolver = $container->getResolver($controllerType);

            if ($resolver instanceof ClassResolver) {
                $factory = $resolver->getFactory(new ResolutionContext(), $this->route->getParams());
                $this->controller = $factory->createInstance();
            } else {
                $this->controller =   $container->resolve($controllerType);
            }
            $reflection = new ReflectionClass($this->controller);
            $this->handler = $reflection->getMethod($methodName);
        }
        $this->actionParameters = $this->container->resolveMethodParameters($this->handler, $this->route->getParams());
    }

    private function ensureMethodExists($controller, string $methodName)
    {
        $reflection = new ReflectionClass($controller);
        if (!$reflection->hasMethod($methodName)) {
            throw new RuntimeException("Controller {$reflection->getName()} does not have method {$methodName}.");
        }
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getController(): ?object
    {
        return $this->controller;
    }

    public function getHandler(): \ReflectionFunctionAbstract
    {
        return $this->handler;
    }

    public function getActionParams(): array
    {
        return $this->actionParameters;
    }

    public function execute()
    {
        if ($this->handler instanceof \ReflectionMethod) {
            return $this->handler->invokeArgs($this->controller, $this->actionParameters);
        }
        return $this->handler->invokeArgs($this->actionParameters);
    }
}
