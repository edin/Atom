<?php

namespace Atom\Router;

final class Route
{
    use RouteTrait;
    private $method;
    /** @var ActionHandler */
    private $actionHandler;
    private $params = [];

    public function __construct(Router $group, string  $method, string $path, ActionHandler $actionHandler)
    {
        $this->group = $group;
        $this->method = $method;
        $this->path = $path;
        $this->actionHandler = $actionHandler;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function toController(string $controllerType, string $actionName): self
    {
        $this->actionHandler->setController($controllerType, $actionName);
        return $this;
    }

    public function toClosure(callable $closure): self
    {
        $this->actionHandler->setClosure($closure);
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getActionHandler()
    {
        return $this->actionHandler;
    }
}
