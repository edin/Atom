<?php

namespace Atom\Router;

final class Route
{
    private $name;
    private $group;
    private $method;
    private $path;
    private $handler;
    private $middlewares = [];

    public function __construct(RouteGroup $group, string  $method, string $path, $handler)
    {
        $this->group = $group;
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function withName(string $name): Route
    {
        $this->name = $name;
        return $this;
    }

    public function getFullPath(): string
    {
        $prefixPath = ($this->group) ? $this->group->getPrefixPath() : "";
        $prefixPath = rtrim($prefixPath, " /");

        if ($prefixPath != "") {
            $routePath = ltrim($this->path, " /");
            $result = $prefixPath . "/" . $routePath;
        } else {
            $result = $this->path;
        }

        return $result;
    }

    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function getGroup(): RouteGroup
    {
        return $this->group;
    }

    public function getOwnMiddlewares(): array {
        return $this->middlewares;
    }

    public function getMiddlewares(): array
    {
        return \array_merge($this->getGroup()->getMiddlewares(), $this->middlewares);
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function getAction(): Action {

        // TODO: Construct action from handler

        return new Action();
    }
}
