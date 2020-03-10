<?php

namespace Atom\Router;

use Atom\Container\Container;
use Atom\Container\ResolutionContext;
use Atom\Container\Resolver\ClassResolver;
use Atom\Router\Route;
use ReflectionClass;
use ReflectionFunction;
use RuntimeException;

final class Action
{
    private $container;
    private $route;
    private $controller;
    private $handler;
    private $actionArguments = [];
    private $constructorArguments = [];
    private $properties = [];

    public function __construct(Container $container, Route $route)
    {
        $this->container = $container;
        $this->route = $route;
        $this->resolutionContext = new ResolutionContext();

        $actionHandler = $this->route->getActionHandler();

        if ($actionHandler->isClosure()) {
            $this->handler = new ReflectionFunction($actionHandler->getClosure());
        } else {
            $controllerType = $actionHandler->getController();
            $methodName = $actionHandler->getMethodName();
            $this->ensureMethodExists($controllerType, $methodName);

            $resolver = $container->getResolver($controllerType);

            if ($resolver instanceof ClassResolver) {
                $this->controllerFactory = $resolver->getFactory($this->resolutionContext, $this->route->getParams());
                $this->handler = $this->controllerFactory->getMethod($methodName);
                $this->constructorArguments = $this->controllerFactory->getConstructorArguments();
                $this->properties = $this->controllerFactory->getProperties();
            } else {
                $this->controller =  $resolver->resolve($this->resolutionContext, $this->route->getParams());
                $reflection = new ReflectionClass($this->controller);
                $this->handler = $reflection->getMethod($methodName);
            }
        }
        $this->actionArguments = $this->container->resolveMethodParameters($this->handler, $this->route->getParams());
    }

    private function ensureMethodExists($controller, string $methodName): void
    {
        $reflection = new ReflectionClass($controller);
        if (!$reflection->hasMethod($methodName)) {
            throw new RuntimeException("Controller {$reflection->getName()} does not have method {$methodName}.");
        }
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getController(): ?object
    {
        if ($this->controller == null) {
            $this->controller = $this->controllerFactory->createInstance();
        }
        return $this->controller;
    }

    public function getHandler(): \ReflectionFunctionAbstract
    {
        return $this->handler;
    }

    public function getActionArguments(): array
    {
        return $this->actionArguments;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getConstructorArguments(): array
    {
        return $this->constructorArguments;
    }

    public function execute()
    {
        if ($this->handler instanceof \ReflectionMethod) {
            return $this->handler->invokeArgs($this->getController(), $this->actionArguments);
        }
        return $this->handler->invokeArgs($this->actionArguments);
    }
}
