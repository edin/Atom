<?php

namespace Atom\Router;

use Atom\Router\Route;

final class Action
{
    private $route;
    private $controller;
    private $handler;

    public function __construct(Route $route, ?object $controller, \ReflectionFunctionAbstract $handler)
    {
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
        if ($this->handler instanceof \ReflectionMethod) {
            return $this->handler->invokeArgs($this->controller, $this->getParams());
        }

        return $this->handler->invokeArgs($this->getParams());
    }
}
