<?php

namespace Atom\Router;

class RouteGroup
{
    use RouteTrait;
    private $routes = [];
    private $groups = [];

    public function getAllRoutes(): array
    {
        $stack = new \SplStack;
        $result = [];

        foreach ($this->getGroups() as $group) {
            $stack->push($group);
        }

        while (!$stack->isEmpty()) {
            $group = $stack->pop();

            foreach ($group->getRoutes() as $route) {
                $result[] = $route;
            }

            foreach ($group->getGroups() as $group) {
                $stack->push($group);
            }
        }
        return $result;
    }

    public function addGroup(string $path = "", callable $routeBuilder = null): RouteGroup
    {
        $group = new RouteGroup();
        $group->setGroup($this);
        $group->setPath($path);
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
