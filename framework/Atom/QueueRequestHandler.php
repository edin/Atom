<?php

namespace Atom;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

class QueueRequestHandler implements RequestHandlerInterface
{
    private $middleware = [];
    private $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    public function add(array $middleware)
    {
        $this->middleware = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middleware) === 0) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = array_shift($this->middleware);
        return $middleware->process($request, $this);
    }
}