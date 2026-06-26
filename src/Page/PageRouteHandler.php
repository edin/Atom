<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Router\MatchedRoute;
use RuntimeException;

final readonly class PageRouteHandler
{
    public function render(PageRenderer $pages, MatchedRoute $route): mixed
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(PageRouteMetadata::class);

        if (!$metadata instanceof PageRouteMetadata) {
            throw new RuntimeException("Page route metadata is missing.");
        }

        return $pages->render($metadata->pageClass);
    }
}
