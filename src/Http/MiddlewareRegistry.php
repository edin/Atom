<?php

declare(strict_types=1);

namespace Atom\Http;

final class MiddlewareRegistry
{
    /** @var array<class-string<MiddlewareInterface>|MiddlewareInterface> */
    private array $middlewares = [];

    public function add(string|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /** @return array<class-string<MiddlewareInterface>|MiddlewareInterface> */
    public function all(): array
    {
        return $this->middlewares;
    }
}
