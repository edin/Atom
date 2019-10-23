<?php

namespace Atom\Router;

use Atom\Router\Route;

final class Action
{
    private $route;
    private $controller;
    private $handler;
    private $parameters;

    public function __construct(Route $route)
    {
        $this->route = $route;
        $this->parameters = $route->getParameters();

        $handlerDefinition = $route->getHandler();

        if (is_array($handlerDefinition)) {
            // first element is class
            // second element is method name
        } elseif (is_string($handlerDefinition)) {
            $parts = explode("@", $handlerDefinition);
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

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function execute()
    {
        return $this->handler->invokeArgs($this->controller, $this->parameters);
    }
}
