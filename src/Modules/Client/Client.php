<?php

declare(strict_types=1);

namespace Atom\Modules\Client;

use Atom\Module\ModuleContext;
use Atom\Router\RouteEntry;

final readonly class Client
{
    public const DEFAULT_RESOURCE_PATH = "/atom/client/resources";

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
