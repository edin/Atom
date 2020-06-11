<?php

declare(strict_types=1);

namespace Atom\Router;

final class Route
{
    use RouteTrait;
    private $method;
    private ActionHandler $actionHandler;
    private array $routeParams = [];
    private array $queryParams = [];

    public function __construct(Router $group, $method, string $path, ActionHandler $actionHandler)
    {
        $this->group = $group;
        $this->method = $method;
        $this->path = $path;
        $this->actionHandler = $actionHandler;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function setQueryParams(array $params): void
    {
        $this->queryParams = $params;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getParams(): array
    {
        return array_merge($this->queryParams, $this->routeParams);
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
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

    public function getMethod()
    {
        return $this->method;
    }

    public function getActionHandler()
    {
        return $this->actionHandler;
    }

    public function getController(): ?string
    {
        return $this->actionHandler->getController();
    }

    public function getMethodName(): ?string
    {
        return $this->actionHandler->getMethodName();
    }

    public function getClosure(): ?callable
    {
        return $this->actionHandler->getClosure();
    }

    public function isClosure(): bool
    {
        return $this->actionHandler->isClosure();
    }
}
