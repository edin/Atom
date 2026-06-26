<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final readonly class MiddlewarePipeline
{
    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(private array $middlewares, private RequestHandlerInterface $destination)
    {
    }

    public function handle(Request $request): Response
    {
        return $this->next(0)->handle($request);
    }

    private function next(int $index): RequestHandlerInterface
    {
        if (!isset($this->middlewares[$index])) {
            return $this->destination;
        }

        return new MiddlewareRequestHandler($this->middlewares[$index], $this->next($index + 1));
    }
}
