<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final readonly class MiddlewareRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $next
    ) {
    }

    public function handle(Request $request): Response
    {
        return $this->middleware->process($request, $this->next);
    }
}
