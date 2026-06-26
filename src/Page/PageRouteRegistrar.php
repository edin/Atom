<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;

final readonly class PageRouteRegistrar
{
    public function __construct(private PageDiscovery $discovery = new PageDiscovery())
    {
    }

    /**
     * @return RouteEntry[]
     */
    public function registerDirectory(Router $router, string $directory): array
    {
        return $this->register($router, $this->discovery->discover($directory));
    }

    /**
     * @param PageDescriptor[] $descriptors
     * @return RouteEntry[]
     */
    public function register(Router $router, array $descriptors): array
    {
        $entries = [];
        usort($descriptors, fn(PageDescriptor $left, PageDescriptor $right): int =>
            $this->pathScore($right->path) <=> $this->pathScore($left->path)
        );

        foreach ($descriptors as $descriptor) {
            $entry = RouteEntry::route(
                $descriptor->method,
                $descriptor->path,
                RouteAction::fromMethod(PageRouteHandler::class, "render")
            )->metadata(new PageRouteMetadata($descriptor->pageClass));

            if ($descriptor->name !== null) {
                $entry->name($descriptor->name);
            }

            $router->add($entry);
            $entries[] = $entry;
        }

        return $entries;
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
}
