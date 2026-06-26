<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Di\InjectionContext;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Http\RequestHandlerInterface;
use Atom\Router\MatchedRoute;

final readonly class MatchedRouteHandler implements RequestHandlerInterface
{
    public function __construct(
        private MatchedRoute $route,
        private RouteInvoker $invoker,
        private ResultConverter $resultConverter,
        private InjectionContext $context
    ) {
    }

    public function handle(Request $request): Response
    {
        return $this->resultConverter->toResponse(
            $this->invoker->invoke($this->route, $this->context),
            $this->context
        );
    }
}
