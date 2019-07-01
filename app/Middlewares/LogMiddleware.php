<?php

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

final class LogMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $path = $request->getUri()->getPath();

        $newRequest = $request->withAddedHeader("X-ROCK", "LogMiddleware");

        $result =  $handler->handle($newRequest);

        return $result->withAddedHeader("X-ROCK", "LogMiddleware");
    }
}