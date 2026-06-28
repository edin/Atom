<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

use Atom\Http\Response;
use Atom\Router\MatchedRoute;
use Atom\Router\Router;

final readonly class ApiExplorerHandler
{
    public function __construct(
        private Router $router,
        private ApiModelBuilder $builder = new ApiModelBuilder(),
        private ApiExplorerHtmlRenderer $renderer = new ApiExplorerHtmlRenderer()
    ) {
    }

    public function index(Response $response, MatchedRoute $route): Response
    {
        return $response
            ->header("Content-Type", "text/html; charset=utf-8")
            ->content($this->renderer->render(
                $this->builder->describe($this->router, $this->apiPathPrefix($route)),
                $this->resourcePath($route)
            ));
    }

    private function resourcePath(MatchedRoute $route): string
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(ApiExplorerRouteMetadata::class);

        return $metadata instanceof ApiExplorerRouteMetadata ? $metadata->resourcePath : "/atom/api/resources";
    }

    private function apiPathPrefix(MatchedRoute $route): string
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(ApiExplorerRouteMetadata::class);

        return $metadata instanceof ApiExplorerRouteMetadata ? $metadata->apiPathPrefix : "/api";
    }
}
