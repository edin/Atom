<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\ApiExplorer\UI\Components\AppShell;
use Atom\ApiExplorer\UI\Components\EndpointDetails;
use Atom\ApiExplorer\UI\Components\EndpointList;
use Atom\ApiExplorer\UI\Components\TryRequestPanel;

final readonly class ApiExplorerModule implements ModuleInterface
{
    public function __construct(
        private string $path = "/atom/api",
        private string $apiPathPrefix = "/api"
    ) {
    }

    public function register(ModuleContext $context): void
    {
        $resourcePath = rtrim($this->path, " /") . "/resources";
        foreach ($context->resources($resourcePath, __DIR__ . "/UI/Resources") as $entry) {
            $entry->metadata(new ApiExplorerHidden());
        }

        $context->component("ApiExplorer.AppShell", AppShell::class);
        $context->component("ApiExplorer.EndpointList", EndpointList::class);
        $context->component("ApiExplorer.EndpointDetails", EndpointDetails::class);
        $context->component("ApiExplorer.TryRequest", TryRequestPanel::class);

        foreach ($context->withBasePath($this->path)->pages(__DIR__ . "/UI/Pages") as $entry) {
            $entry->metadata(new ApiExplorerRouteMetadata($resourcePath, $this->apiPathPrefix));
            $entry->metadata(new ApiExplorerHidden());
        }

        $context->route(
            RouteEntry::route("GET", $this->path, RouteAction::fromMethod(ApiExplorerHandler::class, "index"))
                ->name("atom.api-explorer")
                ->title("API Explorer")
                ->description("Inspect registered API routes.")
                ->metadata(new ApiExplorerRouteMetadata($resourcePath, $this->apiPathPrefix))
                ->metadata(new ApiExplorerHidden())
        );

    }
}
