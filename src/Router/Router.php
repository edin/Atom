<?php

declare(strict_types=1);

namespace Atom\Router;

use Closure;

class Router
{
    use RouteTrait;
    private $routes = [];
    private $groups = [];
    private ?string $controllerType = null;

    public static function fromGroupAndPath(Router $router, string $path): Router
    {
        $group = new Router;
        $group->setGroup($router);
        $group->setPath($path);
        return $group;
    }

    /**
     * @return Route[]
     */
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

    public function addGroup(string $path = "", ?Closure $routeBuilder = null): self
    {
        $this->groups[] = $group = Router::fromGroupAndPath($this, $path);
        if ($routeBuilder !== null) {
            $routeBuilder($group);
        }
        return $group;
    }

    public function controller(string $controller, Closure $routeBuilder)
    {
        $this->groups[] = $group = Router::fromGroupAndPath($this, $this->getPath());
        $group->setController($controller);
        $routeBuilder($group);
    }

    public function setController(string $controller)
    {
        $this->controllerType = $controller;
    }

    public function getController(): ?string
    {
        return $this->controllerType;
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

    public function add(Route $route): Route
    {
        $this->routes[] = $route;
        return $route;
    }

    /**
     * @var string|array $method
     * @var string|array|ActionHandler $handler
     */
    public function addRoute($method, string $path, $handler = null): Route
    {
        if (!($handler instanceof ActionHandler)) {
            if ($handler == null) {
                $handler = [$this->controllerType, $path];
            } elseif (is_string($handler)) {
                $handler = [$this->controllerType, $handler];
            }


            $handler = ActionHandler::from($handler);
        }

        $route = new Route($this, $method, $path, $handler);
        return $this->add($route);
    }

    public function getOrPost(string $path, $handler = null): Route
    {
        return $this->addRoute(["GET", "POST"], $path, $handler);
    }

    public function get(string $path, $handler = null): Route
    {
        return $this->addRoute("GET", $path, $handler);
    }

    public function post(string $path, $handler = null): Route
    {
        return $this->addRoute("POST", $path, $handler);
    }

    public function put(string $path, $handler = null): Route
    {
        return $this->addRoute("PUT", $path, $handler);
    }

    public function patch(string $path, $handler = null): Route
    {
        return $this->addRoute("PATCH", $path, $handler);
    }

    public function delete(string $path, $handler = null): Route
    {
        return $this->addRoute("DELETE", $path, $handler);
    }

    public function options(string $path, $handler = null): Route
    {
        return $this->addRoute("OPTIONS", $path, $handler);
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
