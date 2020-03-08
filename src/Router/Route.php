<?php

namespace Atom\Router;

final class Route
{
    use RouteTrait;
    private $method;
    private $handler;
    private $params = [];

    public function __construct(RouteGroup $group, string  $method, string $path, $handler)
    {
        $this->group = $group;
        $this->method = $method;
        $this->path = $path;
        //TODO: Convert to RouteHandler
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
        $prefixPath = ($this->group) ? $this->group->getPrefixPath() : "";
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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHandler()
    {
        return $this->handler;
    }
}
