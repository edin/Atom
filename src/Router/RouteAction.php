<?php

declare(strict_types=1);

namespace Atom\Router;

use Closure;
use InvalidArgumentException;

final readonly class RouteAction
{
    private function __construct(
        public ?string $controllerType,
        public ?string $methodName,
        public ?Closure $closure
    ) {
    }

    public static function from(mixed $handler): self
    {
        if ($handler instanceof self) {
            return $handler;
        }

        if (is_array($handler) && count($handler) == 2) {
            $controller = $handler[0];
            $method = $handler[1];

            if (is_string($controller) && is_string($method)) {
                return self::method($controller, $method);
            }
        }

        if (is_string($handler) && class_exists($handler)) {
            return self::method($handler);
        }

        if (is_callable($handler)) {
            return self::closure($handler);
        }

        throw new InvalidArgumentException("Failed to construct RouteAction from given parameters.");
    }

    public static function method(string $controller, string $methodName = "__invoke"): self
    {
        return new self($controller, $methodName, null);
    }

    public static function closure(callable $closure): self
    {
        return new self(null, null, Closure::fromCallable($closure));
    }

    public function isControllerMethod(): bool
    {
        return $this->controllerType !== null;
    }

    public function isClosure(): bool
    {
        return $this->closure !== null;
    }
}
