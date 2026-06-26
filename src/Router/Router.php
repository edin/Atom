<?php

declare(strict_types=1);

namespace Atom\Router;

use Atom\Http\MiddlewareInterface;

class Router
{
    private string $path = "";
    private ?Router $parent = null;
    /** @var array<class-string<MiddlewareInterface>|MiddlewareInterface> */
    private array $middlewares = [];
    /** @var array<RouteEntry|Router> */
    private array $items = [];

    public function __construct(string $path = "")
    {
        $this->path = $path;
    }

    /**
     * @return RouteEntry[]
     */
    public function getAllRoutes(): array
    {
        $result = [];

        foreach ($this->items as $item) {
            if ($item instanceof RouteEntry) {
                $result[] = $item;
            } else {
                foreach ($item->getAllRoutes() as $entry) {
                    $result[] = $entry;
                }
            }
        }

        return $result;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getFullPath(): string
    {
        $prefixPath = rtrim($this->parent?->getFullPath() ?? "", " /");
        $routerPath = ltrim($this->path, " /");

        if ($routerPath != "") {
            $routerPath = "/" . $routerPath;
        }

        $result = $prefixPath . $routerPath;

        return $result == "" ? "/" : $result;
    }

    public function middleware(string|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function getOwnMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getMiddlewares(): array
    {
        return array_merge($this->parent?->getMiddlewares() ?? [], $this->middlewares);
    }

    /**
     * @return array<RouteEntry|Router>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return RouteEntry[]
     */
    public function getRoutes(): array
    {
        return array_values(array_filter($this->items, function (RouteEntry|Router $item): bool {
            return $item instanceof RouteEntry;
        }));
    }

    public function add(RouteEntry|Router $item): RouteEntry|Router
    {
        if ($item instanceof RouteEntry) {
            $item->bindRouter($this);
        } else {
            $item->parent = $this;
        }

        $this->items[] = $item;
        return $item;
    }
}
