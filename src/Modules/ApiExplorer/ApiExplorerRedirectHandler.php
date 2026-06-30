<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer;

use Atom\Http\Response;
use Atom\Router\MatchedRoute;
use RuntimeException;

final readonly class ApiExplorerRedirectHandler
{
    public function redirect(MatchedRoute $route, Response $response): Response
    {
        $config = $route->getRouteEntry()->getMetadataOfType(ApiExplorerConfig::class);

        if (!$config instanceof ApiExplorerConfig) {
            throw new RuntimeException("API Explorer route is missing configuration.");
        }

        return $response->redirect($config->pagePath);
    }
}
