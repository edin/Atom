<?php

namespace Atom\Router;

final class RouteGroup {

    // private $middlewares = [];
    private $routes = [];

    // public function addMiddleware() {

    // }

    public function addRoute($method, $path, $handler): Route {
        $route = new \Route;
        $route->method = $method;
        $route->path = $path;
        $route->handler = $handler;
        $this->routes[] = $route;

        return $route;
    }
}