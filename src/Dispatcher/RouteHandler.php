<?php

namespace Atom\Dispatcher;

use ReflectionClass;
use RuntimeException;
use Atom\Router\Route;
use Atom\Router\Action;
use ReflectionFunction;
use Atom\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $actionHandler = $route->getActionHandler();

        if ($actionHandler->isClosure()) {
            $method = new ReflectionFunction($actionHandler->getClosure());
            return new Action($this->container, $route, null, $method);
        }

        //TODO: Controller needs to be resolved using Request scope
        $methodName = $actionHandler->getMethodName();
        $controller = $this->container->resolve($actionHandler->getController());
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod($methodName);

        if ($method == null) {
            throw new RuntimeException("Class {$reflection->getName()} does not contain method {$methodName}.");
        }

        return new Action($this->container, $route, $controller, $method);
    }
}
