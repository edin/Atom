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
        $options = $route->getRouteEntry()->getMetadataOfType(ApiExplorerOptions::class);

        if (!$options instanceof ApiExplorerOptions) {
            throw new RuntimeException("API Explorer route is missing configuration.");
        }

        return $response->redirect($options->pagePath);
    }
}
