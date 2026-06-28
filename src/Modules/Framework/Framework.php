<?php

declare(strict_types=1);

namespace Atom\Modules\Framework;

use Atom\Module\ModuleContext;
use Atom\Modules\Framework\Components\FieldError;
use Atom\Modules\Framework\Components\ValidationSummary;
use Atom\Router\RouteEntry;

final readonly class Framework
{
    public const DEFAULT_RESOURCE_PATH = "/atom/framework/resources";

    public static function module(string $resourcePath = self::DEFAULT_RESOURCE_PATH): FrameworkModule
    {
        return new FrameworkModule($resourcePath);
    }

    public static function components(ModuleContext $context): void
    {
        $context->component("FieldError", FieldError::class);
        $context->component("ValidationSummary", ValidationSummary::class);
    }

    /**
     * @return RouteEntry[]
     */
    public static function resources(ModuleContext $context, string $path = self::DEFAULT_RESOURCE_PATH): array
    {
        $routePath = self::joinPaths($context->basePath, $path, "{path*}");
        foreach ($context->router->getAllRoutes() as $route) {
            if ($route->getFullPath() === $routePath && $route->getMethod() === "GET") {
                return [$route];
            }
        }

        return $context->resources($path, __DIR__ . "/Resources");
    }

    private static function joinPaths(string ...$segments): string
    {
        $segments = array_filter(
            array_map(static fn(string $segment): string => trim($segment, " /"), $segments),
            static fn(string $segment): bool => $segment !== ""
        );

        return "/" . implode("/", $segments);
    }
}
