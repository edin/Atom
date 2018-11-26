<?php

namespace Atom\Router;

final class RouteGroup {

    private $middlewares = [];
    private $routes = [];
    private $path = "";

    public function addMiddleware($middleware) {
        $this->middlewares[] = $middleware;
    }

    public function addRoute($method, $path, $handler): Route {
        $route = new Route;
        $route->group  = $this;
        $route->method = $method;
        $route->path = $path;
        $route->handler = $handler;
        $this->routes[] = $route;
        return $route;
    }

    public function setPrefixPath(string $path) {
        $this->path = $path;
    }

    public function getPrefixPath() {
        return $this->path;
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function getMiddlewares(): array {
        return $this->middlewares;
    }
}