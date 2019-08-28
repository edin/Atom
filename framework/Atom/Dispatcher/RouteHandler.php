<?php

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Container\Container;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RouteHandler implements RequestHandlerInterface
{
    private $container;
    private $route;
    private $routeParams;

    public function __construct(Container $container, Route $route, array $routeParams)
    {
        $this->container = $container;
        $this->route = $route;
        $this->routeParams = $routeParams;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->container;
        $route = $this->route;
        $routeParams = $this->routeParams;
        $handler = $route->getHandler();

        if ($handler instanceof \Closure) {
            $method = new \ReflectionFunction($handler);
            $parameters = $container->resolveMethodParameters($method, $routeParams);
            $result = $method->invokeArgs($parameters);
            return $container->ResultHandler->process($result);
        }

        $parts = \explode("@", $handler);
        $controller = $parts[0] ?? "";
        $methodName = $parts[1] ?? "index";

        $controller = $this->container->Application->resolveController($controller);

        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod($methodName);

        if ($method == null) {
            throw new \Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
        }

        $parameters = $container->resolveMethodParameters($method, $routeParams);
        $result = $method->invokeArgs($controller, $parameters);

        return $container->ResultHandler->process($result);
    }
}
