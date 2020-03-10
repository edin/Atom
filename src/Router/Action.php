<?php

namespace Atom\Router;

use Atom\Container\Container;
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
    private $actionParameters;

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

            $this->controller = $container->resolve($controllerType);
            $reflection = new ReflectionClass($this->controller);

            if (!$reflection->hasMethod($methodName)) {
                throw new RuntimeException("Controller {$reflection->getName()} does not have method {$methodName}.");
            }
            $this->handler = $reflection->getMethod($methodName);
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

    public function getParams(): array
    {
        return $this->route->getParams();
    }

    public function getActionParams(): array
    {
        if ($this->actionParameters === null) {
            $this->actionParameters = $this->container->resolveMethodParameters($this->handler, $this->getParams());
        }
        return $this->actionParameters;
    }

    public function execute()
    {
        $actionParams = $this->getActionParams();

        if ($this->handler instanceof \ReflectionMethod) {
            return $this->handler->invokeArgs($this->controller, $actionParams);
        }

        return $this->handler->invokeArgs($actionParams);
    }
}
