<?php

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Container\Container;
use Atom\Router\Action;
use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionFunction;

class RouteHandler implements RequestHandlerInterface
{
    private $container;
    private $route;

    public function __construct(Container $container, Route $route)
    {
        $this->container = $container;
        $this->route = $route;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $action = $this->createAction($this->route);

        $result = $action->execute([]);

        return $this->container->ResultHandler->process($result);
    }

    private function createAction(Route $route): Action
    {
        $handler = $route->getHandler();

        if ($handler instanceof \Closure) {
            $method = new ReflectionFunction($handler);
            return new Action($this->container, $route, null, $method);
        } elseif (is_string($handler)) {
            $parts = \explode("@", $handler);
            $controllerName = $parts[0] ?? "";
            $methodName = $parts[1] ?? "index";

            $controller = $this->container->Application->resolveController($controllerName);
            $reflectionClass = new ReflectionClass($controller);
            $method = $reflectionClass->getMethod($methodName);

            if ($method == null) {
                throw new Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
            }

            return new Action($this->container, $route, $controller, $method);
        }

        $typeName = gettype($handler);
        throw new Exception("Unsuported handler format, expected string or closure but got ${$typeName}");
    }
}
