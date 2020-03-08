<?php

namespace Atom\Router;

class RouteGroup
{
    use RouteTrait;
    private $routes = [];
    private $groups = [];

    private function setGroup(RouteGroup $group)
    {
        $this->group = $group;
    }

    public function getGroup(): ?RouteGroup
    {
        return $this->group;
    }

    public function addGroup(string $path = "", callable $routeBuilder = null): RouteGroup
    {
        $group = new RouteGroup();
        $group->setPrefixPath($path);
        $group->setGroup($this);
        $this->groups[] = $group;

        if ($routeBuilder !== null) {
            $routeBuilder($group);
        }
        return $group;
    }

    public function getGroups(): array
    {
        return $this->groups;
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

    public function attach(IRouteBuilder $builder): RouteGroup
    {
        $builder->build($this);
        return $this;
    }

    public function attachTo(string $path, IRouteBuilder $builder): RouteGroup
    {
        $group = $this->addGroup($path);
        $builder->build($group);
        return $group;
    }
}
