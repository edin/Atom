<?php

declare(strict_types=1);

namespace Atom\Router;

use Atom\Http\MiddlewareInterface;

final readonly class MatchedRoute
{
    /**
     * @param array<string, mixed> $routeParams
     * @param array<string, mixed> $queryParams
     */
    public function __construct(
        public RouteEntry $route,
        private array $routeParams = [],
        private array $queryParams = []
    ) {
    }

    public function getRouteEntry(): RouteEntry
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return array_merge($this->queryParams, $this->routeParams);
    }

    /**
     * @return array<class-string<MiddlewareInterface>|MiddlewareInterface>
     */
    public function getMiddlewares(): array
    {
        return $this->route->getMiddlewares();
    }

    public function getRouteAction(): RouteAction
    {
        return $this->route->getRouteAction();
    }
}
