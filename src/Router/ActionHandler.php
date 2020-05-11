<?php

declare(strict_types=1);

namespace Atom\Router;

final class ActionHandler
{
    private $controller;
    private $methodName;
    private $closure;

    public function __construct(?string $controller, ?string $methodName, ?callable $closure)
    {
        $this->controller = $controller;
        $this->methodName = $methodName;
        $this->closure = $closure;
    }

    public static function from($controller, ?string $method = null): self
    {
        if (is_callable($controller)) {
            return ActionHandler::fromClosure($controller);
        }

        if (is_string($controller)) {
            return ActionHandler::fromMethod($controller, $method, null);
        }
        throw new \InvalidArgumentException("Failed to construct ActionHandler from given parameters.");
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
