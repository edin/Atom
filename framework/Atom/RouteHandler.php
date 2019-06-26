<?php

namespace Atom;

use Atom\Router\Route;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteHandler implements RequestHandlerInterface
{
    private $app;
    private $route;
    private $routeParams;

    public function __construct(Application $app, Route $route, array $routeParams)
    {
        $this->app = $app;
        $this->route = $route;
        $this->routeParams = $routeParams;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->app->getContainer();
        $route = $this->route;
        $routeParams = $this->routeParams;

        if ($route->handler instanceof \Closure) {
            $method = new \ReflectionFunction($route->handler);
            $parameters = $container->resolveMethodParameters($method, $routeParams);
            $result = call_user_func_array($route->handler, $parameters);
            return $this->app->processResult($result);
        }

        $parts = \explode("@", $route->handler);
        $controller = $parts[0] ?? "";
        $methodName = $parts[1] ?? "index";

        $controller = $this->app->resolveController($controller);

        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod($methodName);

        if ($method == null) {
            throw new \Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
        }

        $container->resolveProperties($controller);
        $parameters = $container->resolveMethodParameters($method, $routeParams);
        $result = call_user_func_array([$controller, $methodName], $parameters);
        return $this->app->processResult($result);
    }
}
