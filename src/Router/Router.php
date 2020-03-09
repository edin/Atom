<?php

namespace Atom\Router;

class Router
{
    use RouteTrait;
    private $routes = [];
    private $groups = [];

    public static function fromGroupAndPath(Router $router, string $path): Router
    {
        $group = new Router;
        $group->setGroup($router);
        $group->setPath($path);
        return $group;
    }

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

    public function addGroup(string $path = "", callable $routeBuilder = null): self
    {
        $this->groups[] = $group = Router::fromGroupAndPath($this, $path);
        if ($routeBuilder !== null) {
            $routeBuilder($group);
        }
        return $group;
    }

    /**
     * @return Router[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function addRoute(string $method, string $path, $controller, $action = null): Route
    {
        $route = new Route($this, $method, $path, ActionHandler::from($controller, $action));
        $this->routes[] = $route;
        return $route;
    }

    public function get(string $path, $controller, $action = null): Route
    {
        return $this->addRoute("GET", $path, $controller, $action);
    }

    public function post(string $path, $controller, $action = null): Route
    {
        return $this->addRoute("POST", $path, $controller, $action);
    }

    public function put(string $path, $controller, $action = null): Route
    {
        return $this->addRoute("PUT", $path, $controller, $action);
    }

    public function patch(string $path, $controller, $action = null): Route
    {
        return $this->addRoute("PATCH", $path, $controller, $action);
    }

    public function delete(string $path, $controller, $action = null): Route
    {
        return $this->addRoute("DELETE", $path, $controller, $action);
    }

    public function options(string $path, $controller, $action = null): Route
    {
        return $this->addRoute("OPTIONS", $path, $controller, $action);
    }

    public function attach(IRouteBuilder $builder): self
    {
        $builder->build($this);
        return $this;
    }

    public function attachTo(string $path, IRouteBuilder $builder): self
    {
        $group = $this->addGroup($path);
        $builder->build($group);
        return $group;
    }
}
