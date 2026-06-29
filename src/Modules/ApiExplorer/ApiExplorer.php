<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer;

use Atom\Di\Injector;
use Atom\Module\ModuleContext;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\View\Component\ComponentRegistry;

final readonly class ApiExplorer
{
    public static function module(string $path = "/atom/api", string $apiPathPrefix = "/api"): ApiExplorerModule
    {
        return new ApiExplorerModule($path, $apiPathPrefix);
    }

    public static function register(Router $router, string $path = "/atom/api", string $apiPathPrefix = "/api"): RouteEntry
    {
        self::module($path, $apiPathPrefix)->register(new ModuleContext($router, Injector::create(), new ComponentRegistry()));

        foreach ($router->getAllRoutes() as $route) {
            if ($route->getName() === "atom.api-explorer") {
                return $route;
            }
        }

        throw new \RuntimeException("API explorer route was not registered.");
    }
}
