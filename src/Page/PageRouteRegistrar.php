<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\MiddlewareInterface;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;

final readonly class PageRouteRegistrar
{
    public function __construct(
        private PageDiscovery $discovery = new PageDiscovery(),
        private PageActionDiscovery $actions = new PageActionDiscovery()
    )
    {
    }

    /**
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     * @return RouteEntry[]
     */
    public function registerDirectory(
        Router $router,
        string $directory,
        string $pathPrefix = "",
        array $middlewares = []
    ): array
    {
        return $this->register($router, $this->discovery->discover($directory), $pathPrefix, $middlewares);
    }

    /**
     * @param PageDescriptor[] $descriptors
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     * @return RouteEntry[]
     */
    public function register(Router $router, array $descriptors, string $pathPrefix = "", array $middlewares = []): array
    {
        $entries = [];
        usort($descriptors, fn(PageDescriptor $left, PageDescriptor $right): int =>
            $this->pathScore($right->path) <=> $this->pathScore($left->path)
        );

        foreach ($descriptors as $descriptor) {
            $entry = RouteEntry::create(
                "GET",
                $this->joinPaths($pathPrefix, $descriptor->path),
                RouteAction::method(PageRouteHandler::class, "render")
            )->metadata(new PageRouteMetadata($descriptor->pageClass));

            if ($descriptor->name !== null) {
                $entry->name($descriptor->name);
            }

            $this->applyPresentation($entry, $descriptor);
            $this->applyMiddlewares($entry, [...$middlewares, ...$descriptor->middlewares]);

            $router->add($entry);
            $entries[] = $entry;

            foreach ($this->actionMethods($descriptor->pageClass) as $method) {
                $actionEntry = RouteEntry::create(
                    $method,
                    $this->joinPaths($pathPrefix, $descriptor->path),
                    RouteAction::method(PageActionHandler::class, "handle")
                )->metadata(new PageRouteMetadata($descriptor->pageClass));

                $this->applyPresentation($actionEntry, $descriptor);
                $this->applyMiddlewares($actionEntry, [...$middlewares, ...$descriptor->middlewares]);

                $router->add($actionEntry);
                $entries[] = $actionEntry;
            }
        }

        return $entries;
    }

    private function joinPaths(string $prefix, string $path): string
    {
        $prefix = rtrim($prefix, " /");
        $path = ltrim($path, " /");

        if ($path !== "") {
            $path = "/" . $path;
        }

        $result = $prefix . $path;

        return $result === "" ? "/" : $result;
    }

    private function pathScore(string $path): int
    {
        $score = 0;

        foreach (explode("/", trim($path, " /")) as $segment) {
            if ($segment === "") {
                continue;
            }

            $score += str_starts_with($segment, "{") && str_ends_with($segment, "}") ? 1 : 10;
        }

        return $score;
    }

    /**
     * @param class-string<Page> $pageClass
     * @return string[]
     */
    private function actionMethods(string $pageClass): array
    {
        return $this->actions->methods($pageClass);
    }

    private function applyPresentation(RouteEntry $entry, PageDescriptor $descriptor): void
    {
        if ($descriptor->title !== null) {
            $entry->title($descriptor->title);
        }

        if ($descriptor->description !== null) {
            $entry->description($descriptor->description);
        }
    }

    /**
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     */
    private function applyMiddlewares(RouteEntry $entry, array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $entry->middleware($middleware);
        }
    }
}
