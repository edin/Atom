<?php

declare(strict_types=1);

namespace Atom\Modules\DevReload;

use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Router\RouteEntry;

final readonly class DevReloadModule implements ModuleInterface
{
    /**
     * @param string[] $watchPaths
     */
    public function __construct(
        private array $watchPaths = ["@root/app", "@root/public", "@root/../src"],
        private string $resourcePath = "/resources"
    ) {
    }

    public function register(ModuleContext $context): void
    {
        $context->resources($this->resourcePath, __DIR__ . "/Resources");

        $context->route(RouteEntry::get(
            $context->mountedPath("/reload-version"),
            [DevReloadVersionHandler::class, "handle"]
        ));

        $context->bind(DevReloadOptions::class)->toValue(new DevReloadOptions($this->watchPaths));
    }
}
