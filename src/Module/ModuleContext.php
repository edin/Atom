<?php

declare(strict_types=1);

namespace Atom\Module;

use Atom\Di\Injector;
use Atom\Http\StaticFileHandler;
use Atom\Http\StaticFileRouteMetadata;
use Atom\Page\PageRouteRegistrar;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;

final readonly class ModuleContext
{
    public function __construct(
        public Router $router,
        public Injector $injector,
        public ComponentRegistry $components,
        public string $basePath = ""
    ) {
    }

    public function withBasePath(string $basePath): self
    {
        return new self($this->router, $this->injector, $this->components, $basePath);
    }

    public function route(RouteEntry $entry): RouteEntry
    {
        return $this->router->add($entry);
    }

    /**
     * @param class-string<ComponentInterface> $className
     */
    public function component(string $name, string $className): self
    {
        $this->components->register($name, $className);

        return $this;
    }

    /**
     * @return RouteEntry[]
     */
    public function pages(string $directory): array
    {
        return (new PageRouteRegistrar())->registerDirectory($this->router, $directory, $this->basePath);
    }

    /**
     * @return RouteEntry[]
     */
    public function resources(string $path, string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $entry = RouteEntry::route(
            "GET",
            $this->joinPaths($path, "{path*}"),
            RouteAction::fromMethod(StaticFileHandler::class, "serve")
        )->metadata(new StaticFileRouteMetadata($directory));

        $this->route($entry);

        return [$entry];
    }

    private function joinPaths(string $path, string $relativePath): string
    {
        $base = rtrim($this->basePath, " /");
        $path = trim($path, " /");
        $relativePath = ltrim($relativePath, " /");
        $segments = array_filter([$base, $path, $relativePath], static fn(string $segment): bool => $segment !== "");

        return "/" . implode("/", array_map(static fn(string $segment): string => trim($segment, " /"), $segments));
    }

}
