<?php

declare(strict_types=1);

namespace Atom\Router;

use Closure;

final class ActionHandler
{
    private ?string $controller;
    private ?string $methodName;
    private $closure;

    public function __construct(?string $controller, ?string $methodName, ?callable $closure)
    {
        $this->controller = $controller;
        $this->methodName = $methodName;
        $this->closure = $closure;
    }

    public static function from($handler): self
    {
        if (is_array($handler) && count($handler) == 2) {
            $controller = $handler[0];
            $method = $handler[1];
            return ActionHandler::fromMethod($controller, $method, null);
        }

        if ($handler instanceof Closure) {
            return ActionHandler::fromClosure($handler);
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
