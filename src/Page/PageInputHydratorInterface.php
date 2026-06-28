<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\Request;
use Atom\Router\MatchedRoute;

interface PageInputHydratorInterface
{
    public function hydrate(Page $page, Request $request, MatchedRoute $route): void;
}
