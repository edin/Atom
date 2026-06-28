<?php

declare(strict_types=1);

namespace Atom\Module;

use Atom\Di\Injector;
use Atom\Page\PageRouteRegistrar;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\ComponentRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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

        $entries = [];
        $root = rtrim(str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $directory), DIRECTORY_SEPARATOR);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();
            $relativePath = str_replace(DIRECTORY_SEPARATOR, "/", substr($filePath, strlen($root) + 1));
            $entry = RouteEntry::route(
                "GET",
                $this->joinPaths($path, $relativePath),
                RouteAction::fromMethod(ResourceRouteHandler::class, "serve")
            )->metadata(new ResourceRouteMetadata($filePath, $this->contentType($filePath)));

            $this->router->add($entry);
            $entries[] = $entry;
        }

        return $entries;
    }

    private function joinPaths(string $path, string $relativePath): string
    {
        $base = rtrim($this->basePath, " /");
        $path = trim($path, " /");
        $relativePath = ltrim($relativePath, " /");
        $segments = array_filter([$base, $path, $relativePath], static fn(string $segment): bool => $segment !== "");

        return "/" . implode("/", array_map(static fn(string $segment): string => trim($segment, " /"), $segments));
    }

    private function contentType(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            "css" => "text/css; charset=utf-8",
            "js" => "application/javascript; charset=utf-8",
            "json" => "application/json; charset=utf-8",
            "svg" => "image/svg+xml",
            "png" => "image/png",
            "jpg", "jpeg" => "image/jpeg",
            "gif" => "image/gif",
            "webp" => "image/webp",
            "ico" => "image/x-icon",
            default => "application/octet-stream",
        };
    }
}
