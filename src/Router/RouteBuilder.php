<?php

declare(strict_types=1);

namespace Atom\Router;

use ReflectionClass;
use ReflectionMethod;

class RouteBuilder implements IRouteBuilder
{
    private $controllerType;

    public function __construct(string $controllerType)
    {
        $this->controllerType = $controllerType;
    }

    public function build(Router $router)
    {
        $classType = new ReflectionClass($this->controllerType);
        $methods = $classType->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $route = $this->getRouteInfo($method);
            if ($route) {
                $methodName = $method->getName();
                $router->addRoute($route->method, $route->path, $this->controllerType, $methodName);
            } else {
                $methodName = $method->getName();
                $router->addRoute("GET", $methodName, $this->controllerType, $methodName);
            }
        }
    }

    private function getRouteInfo(ReflectionMethod $method)
    {
        $comment = $method->getDocComment();
        $result = preg_match('/@(Get|Post|Put|Patch|Delete|Head|Options)\(\"(.*)\"\)/', $comment, $matches);
        if ($result) {
            $info = new \stdClass;
            $info->method = strtoupper($matches[1]);
            $info->path = $matches[2];
            return $info;
        }
        return null;
    }

    public static function fromController(string $controllerType): self
    {
        return new self($controllerType);
    }
}
