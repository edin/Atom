<?php

declare(strict_types=1);

namespace Atom\Modules\Client;

use Atom\Module\ModuleContext;
use Atom\Router\RouteEntry;
use Atom\View\Component\ComponentSet;

final readonly class Client
{
    public const DEFAULT_RESOURCE_PATH = "/atom/client/resources";
    public const ATOM_VERSION = "9";
    public const MORPHDOM_VERSION = "2.7.8";
    public const MORPHDOM_ADAPTER_VERSION = "3";

    public static function definitions(): ComponentSet
    {
        return ComponentSet::from([
            "ClientScripts" => ClientScripts::class,
        ]);
    }

    public static function module(string $resourcePath = self::DEFAULT_RESOURCE_PATH): ClientModule
    {
        return new ClientModule($resourcePath);
    }

    /** @return RouteEntry[] */
    public static function resources(ModuleContext $context, string $path = self::DEFAULT_RESOURCE_PATH): array
    {
        return $context->resources($path, __DIR__ . "/Resources");
    }
}
