<?php

declare(strict_types=1);

namespace Atom\Module;

use Atom\Http\Response;
use Atom\Router\MatchedRoute;
use RuntimeException;

final readonly class ResourceRouteHandler
{
    public function serve(MatchedRoute $route, Response $response): Response
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(ResourceRouteMetadata::class);
        if (!$metadata instanceof ResourceRouteMetadata) {
            throw new RuntimeException("Resource route metadata is missing.");
        }

        $contents = file_get_contents($metadata->file);
        if ($contents === false) {
            return $response->status(404)->content("Not Found");
        }

        return $response
            ->header("Content-Type", $metadata->contentType)
            ->content($contents);
    }
}
