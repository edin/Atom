<?php

namespace Atom\Router;

final class Route
{
    use RouteTrait;
    private $method;
    private $controllerType;
    private $actionName;
    private $handler;
    private $params = [];

    public function __construct(RouteGroup $group, string  $method, string $path, $handler)
    {
        $this->group = $group;
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getFullPath(): string
    {
        $prefixPath = ($this->group) ? $this->group->getPath() : "";
        $prefixPath = rtrim($prefixPath, " /");
        $routePath  = "/" . ltrim($this->path, " /");
        $result = $prefixPath . $routePath;
        return $result;
    }

    public function addActionFilter($actionFilter): self
    {
        $this->actionFilters[] = $actionFilter;
        return $this;
    }

    public function toController(string $controllerType): self
    {
        $this->controllerType = $controllerType;
        return $this;
    }

    public function toAction(string $actionName): self
    {
        $this->actionName = $actionName;
        return $this;
    }

    public function toClosure(callable $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHandler()
    {
        return $this->handler;
    }
}
