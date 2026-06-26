<?php

declare(strict_types=1);

namespace Atom\Router;

final readonly class RouteMatchResult
{
    /**
     * @param string[] $allowedMethods
     */
    private function __construct(
        public ?MatchedRoute $matchedRoute = null,
        public array $allowedMethods = []
    ) {
    }

    public static function found(MatchedRoute $matchedRoute): self
    {
        return new self($matchedRoute);
    }

    /**
     * @param string[] $allowedMethods
     */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(null, array_values(array_unique($allowedMethods)));
    }

    public static function notFound(): self
    {
        return new self();
    }

    public function isFound(): bool
    {
        return $this->matchedRoute !== null;
    }

    public function isMethodNotAllowed(): bool
    {
        return !$this->isFound() && count($this->allowedMethods) > 0;
    }
}
