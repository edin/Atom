<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer;

use Atom\Api\ApiHidden;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Modules\Framework\Framework;
use Atom\Router\RouteEntry;
use Atom\Modules\ApiExplorer\UI\Components\AppShell;
use Atom\Modules\ApiExplorer\UI\Components\EndpointDetails;
use Atom\Modules\ApiExplorer\UI\Components\EndpointList;
use Atom\Modules\ApiExplorer\UI\Components\TryRequestPanel;

final readonly class ApiExplorerModule implements ModuleInterface
{
    public function __construct(
        private string $apiPathPrefix = "/api"
    ) {
    }

    public function register(ModuleContext $context): void
    {
        Framework::resources($context->root());

        $resourcePath = $context->resourcePath();
        $pagePath = $context->mountedPath("/explorer");
        $options = new ApiExplorerOptions($resourcePath, $pagePath, $this->apiPathPrefix);
        foreach ($context->resources("/resources", __DIR__ . "/UI/Resources") as $entry) {
            $entry->metadata(new ApiHidden());
        }

        $context->component("ApiExplorer.AppShell", AppShell::class);
        $context->component("ApiExplorer.EndpointList", EndpointList::class);
        $context->component("ApiExplorer.EndpointDetails", EndpointDetails::class);
        $context->component("ApiExplorer.TryRequest", TryRequestPanel::class);

        foreach ($context->pages(__DIR__ . "/UI/Pages") as $entry) {
            $entry->metadata($options);
            $entry->metadata(new ApiHidden());
        }

        $context->route(
            RouteEntry::create(
                "GET",
                $context->mountedPath(),
                [ApiExplorerRedirectHandler::class, "redirect"]
            )
                ->name("atom.api-explorer")
                ->title("API Explorer")
                ->description("Inspect registered API routes.")
                ->metadata($options)
                ->metadata(new ApiHidden())
        );

    }
}
