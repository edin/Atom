<?php

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Container\Container;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionFunction;

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
        $actionFactory = new ActionFactory();

        $action = $actionFactory->createAction($this->container, $request, $this->route, $this->routeParams);

        $result = $action->execute([]);

        return $this->container->ResultHandler->process($result);
    }
}


class ActionFactory
{
    public function createAction(
        Container $container,
        ServerRequestInterface $request,
        Route $route,
        array $routeParams
    ): Action {
        $handler = $route->getHandler();
        if ($handler instanceof \Closure) {
            $method = new \ReflectionFunction($handler);
            return new ClosureAction($container, $route, $method, $routeParams);
        }

        if (is_string($handler)) {
            return new ControllerAction($container, $route, $routeParams);
        }

        throw new \Exception("Unsuported handler definition.");
    }
}

class Action
{
}

class ClosureAction extends Action
{
    private $container;
    private $method;
    private $routeParams;

    public function __construct(
        Container $container,
        Route $route,
        ReflectionFunction $method,
        array $routeParams
    ) {
        $this->container = $container;
        $this->route = $route;
        $this->method = $method;
        $this->routeParams = $routeParams;
    }

    public function execute(array $parameters = [])
    {
        $parameters = $this->container->resolveMethodParameters($this->method, $this->routeParams);
        return $this->method->invokeArgs($parameters);
    }
}

class ControllerAction extends Action
{
    private $container;
    private $method;
    private $routeParams;

    public function __construct(
        Container $container,
        Route $route,
        array $routeParams
    ) {
        $this->container = $container;
        $this->route = $route;
        $this->routeParams = $routeParams;

        $handler = $route->getHandler();

        $parts = \explode("@", $handler);
        $controller = $parts[0] ?? "";
        $methodName = $parts[1] ?? "index";

        $this->controller = $this->container->Application->resolveController($controller);
        $reflectionClass = new \ReflectionClass($this->controller);
        $this->method = $reflectionClass->getMethod($methodName);

        if ($this->method == null) {
            throw new \Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
        }
    }

    public function execute()
    {
        $parameters = $this->container->resolveMethodParameters($this->method, $this->routeParams);
        return $this->method->invokeArgs($this->controller, $parameters);
    }
}
