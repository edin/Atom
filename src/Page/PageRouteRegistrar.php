<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

final readonly class PageRouteRegistrar
{
    public function __construct(private PageDiscovery $discovery = new PageDiscovery())
    {
    }

    /**
     * @return RouteEntry[]
     */
    public function registerDirectory(Router $router, string $directory, string $pathPrefix = ""): array
    {
        return $this->register($router, $this->discovery->discover($directory), $pathPrefix);
    }

    /**
     * @param PageDescriptor[] $descriptors
     * @return RouteEntry[]
     */
    public function register(Router $router, array $descriptors, string $pathPrefix = ""): array
    {
        $entries = [];
        usort($descriptors, fn(PageDescriptor $left, PageDescriptor $right): int =>
            $this->pathScore($right->path) <=> $this->pathScore($left->path)
        );

        foreach ($descriptors as $descriptor) {
            $entry = RouteEntry::route(
                "GET",
                $this->joinPaths($pathPrefix, $descriptor->path),
                RouteAction::fromMethod(PageRouteHandler::class, "render")
            )->metadata(new PageRouteMetadata($descriptor->pageClass));

            if ($descriptor->name !== null) {
                $entry->name($descriptor->name);
            }

            $router->add($entry);
            $entries[] = $entry;

            foreach ($this->actionMethods($descriptor->pageClass) as $method) {
                $actionEntry = RouteEntry::route(
                    $method,
                    $this->joinPaths($pathPrefix, $descriptor->path),
                    RouteAction::fromMethod(PageActionHandler::class, "handle")
                )->metadata(new PageRouteMetadata($descriptor->pageClass));

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
        $methods = [];
        $reflection = new ReflectionClass($pageClass);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(PageAction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $action = $attribute->newInstance();
                $methods[strtoupper($action->method)] = strtoupper($action->method);
            }
        }

        return array_values($methods);
    }
}
