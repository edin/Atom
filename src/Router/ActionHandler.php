<?php

namespace Atom\Router;

final class ActionHandler
{
    // private const ControllerMethod = 1;
    // private const Closure = 2;

    /** @var ?string */
    private $controller;
    /** @var ?string */
    private $methodName;
    /** @var ?callable */
    private $closure;

    public function __construct(?string $controller, ?string $methodName, ?callable $closure)
    {
        $this->controller = $controller;
        $this->methodName = $methodName;
        $this->closure = $closure;
    }

    public static function from($controller, ?string $action = null): self
    {
        if (is_callable($controller)) {
            return ActionHandler::fromClosure($controller);
        }

        if (is_string($controller)) {
            return new ActionHandler($controller, $action, null);
        }
        throw new \InvalidArgumentException("Failed to construct ActionHandler from given parameters.");
    }

    public static function fromString(string $target): self
    {
        $parts = explode("@", $target);
        $controller = $parts[0];
        $action = $parts[1] ?? "index";
        return new ActionHandler($controller, $action, null);
    }

    public static function fromMethod(string $controller, string $methodName): self
    {
        return new ActionHandler($controller, $methodName, null);
    }

    public static function fromClosure(callable $closure): self
    {
        return new ActionHandler(null, null, $closure);
    }

    public function setController(string $controller, string $methodName): void
    {
        $this->controller = $controller;
        $this->methodName = $methodName;
        $this->closure = null;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }

    public function setClosure(callable $closure): void
    {
        $this->closure = $closure;
    }

    public function getClosure(): ?callable
    {
        return $this->closure;
    }

    public function isClosure(): bool
    {
        return $this->closure !== null;
    }
}
