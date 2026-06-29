<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer;

use Atom\Api\ApiHidden;
use Atom\Http\Response;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Modules\Framework\Framework;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Modules\ApiExplorer\UI\Components\AppShell;
use Atom\Modules\ApiExplorer\UI\Components\EndpointDetails;
use Atom\Modules\ApiExplorer\UI\Components\EndpointList;
use Atom\Modules\ApiExplorer\UI\Components\TryRequestPanel;

final readonly class ApiExplorerModule implements ModuleInterface
{
    public function __construct(
        private string $path = "/atom/api",
        private string $apiPathPrefix = "/api"
    ) {
    }

    public function register(ModuleContext $context): void
    {
        Framework::resources($context);

        $resourcePath = rtrim($this->path, " /") . "/resources";
        $pagePath = rtrim($this->path, " /") . "/explorer";
        foreach ($context->resources($resourcePath, __DIR__ . "/UI/Resources") as $entry) {
            $entry->metadata(new ApiHidden());
        }

        $context->component("ApiExplorer.AppShell", AppShell::class);
        $context->component("ApiExplorer.EndpointList", EndpointList::class);
        $context->component("ApiExplorer.EndpointDetails", EndpointDetails::class);
        $context->component("ApiExplorer.TryRequest", TryRequestPanel::class);

        foreach ($context->withBasePath($this->path)->pages(__DIR__ . "/UI/Pages") as $entry) {
            $entry->metadata(new ApiExplorerRouteMetadata($resourcePath, $this->apiPathPrefix));
            $entry->metadata(new ApiHidden());
        }

        $context->route(
            RouteEntry::route(
                "GET",
                $this->path,
                RouteAction::fromClosure(fn(Response $response): Response => $response->redirect($pagePath))
            )
                ->name("atom.api-explorer")
                ->title("API Explorer")
                ->description("Inspect registered API routes.")
                ->metadata(new ApiExplorerRouteMetadata($resourcePath, $this->apiPathPrefix))
                ->metadata(new ApiHidden())
        );

    }
}
