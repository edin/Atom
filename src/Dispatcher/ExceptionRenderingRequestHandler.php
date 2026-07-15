<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Closure;
use Throwable;

final readonly class ExceptionRenderingRequestHandler implements RequestHandlerInterface
{
    private Closure $renderer;

    /** @param callable(Throwable, Request): Response $renderer */
    public function __construct(private RequestHandlerInterface $handler, callable $renderer)
    {
        $this->renderer = Closure::fromCallable($renderer);
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->handler->handle($request);
        } catch (Throwable $exception) {
            return ($this->renderer)($exception, $request);
        }
    }
}
