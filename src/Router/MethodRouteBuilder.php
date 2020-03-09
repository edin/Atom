<?php

namespace Atom\Router;

use ReflectionClass;
use ReflectionMethod;

class MethodRouteBuilder implements IRouteBuilder
{
    private $controllerType;

    public function __construct(string $controllerType)
    {
        $this->controllerType = $controllerType;
    }

    public function build(Router $group)
    {
        $classType = new ReflectionClass($this->controllerType);
        $methods = $classType->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $group->addRoute("GET", $method->getName(), $this->controllerType . ":" . $method->getName());
        }
    }
}
