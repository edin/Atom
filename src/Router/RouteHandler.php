<?php

namespace Atom\Router;

final class RouteHandler
{
    private $controller;
    private $methodName;
    private $closure;

    public function __construct($controller, $methodName, $closure)
    {
        $this->controller = $controller;
        $this->methodName = $methodName;
        $this->closure = $closure;
    }

    public static function fromString(string $target)
    {
        $parts = explode("@", $target);
        $controller = $parts[0];
        $action = $parts[1] ?? "index";
        return new RouteHandler($controller, $action, null);
    }

    public static function fromMethod(string $controller, string $methodName): RouteHandler
    {
        return new RouteHandler($controller, $methodName, null);
    }

    public static function fromClosure($closure): RouteHandler
    {
        return new RouteHandler(null, null, $closure);
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getMethodName()
    {
        return $this->methodName;
    }

    public function getClosure()
    {
        return $this->closure;
    }
}
