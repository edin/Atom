<?php

namespace Atom\Router;

use Atom\Container\Container;
use Atom\Router\Route;

final class Action
{
    private $route;
    private $controller;
    private $handler;

    public function __construct(Container $container, Route $route, ?object $controller, \ReflectionFunctionAbstract $handler)
    {
        $this->container = $container;
        $this->route = $route;
        $this->controller = $controller;
        $this->handler = $handler;
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

    public function execute()
    {
        $parameters = $this->container->resolveMethodParameters($this->handler, $this->getParams());

        if ($this->handler instanceof \ReflectionMethod) {
            return $this->handler->invokeArgs($this->controller, $parameters);
        }

        return $this->handler->invokeArgs($parameters);
    }
}
