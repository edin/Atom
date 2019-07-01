<?php

namespace Atom\Router;

class RouteGroup
{
    private $groups = [];
    private $middlewares = [];
    private $routes = [];
    private $path = "";
    private $parent = null;

    private function setParent(RouteGroup $parent) {
        $this->parent = $parent;
    }

    public function getParent(): ?RouteGroup {
        return $this->parent;
    }

    public function addGroup(string $path = "", callable $routes = null): RouteGroup
    {
        $group = new RouteGroup();
        $group->setPrefixPath($path);
        $group->setParent($this);
        $this->groups[] = $group;

        if ($routes !== null) {
            $routes($group);
        }
        return $group;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function addRoute(string $method, string $path, $handler): Route
    {
        $route = new Route($this, $method, $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    public function setPrefixPath(string $path)
    {
        $this->path = $path;
    }

    public function getPrefixPath()
    {
        return $this->path;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getOwnMidlewares(): array {
        return $this->middlewares;
    }

    public function getMiddlewares(): array
    {
        if ($this->parent) {
            return \array_merge($this->parent->getMiddlewares(), $this->middlewares);
        }
        return $this->middlewares;
    }
}
