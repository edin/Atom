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
        return $context->resources($path, __DIR__ . "/Resources");
    }
}
