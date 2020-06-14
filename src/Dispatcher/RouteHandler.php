<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Router\Action;
use Atom\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteHandler implements RequestHandlerInterface
{
    private Container $container;

    public function __construct(Container $container, Action $action)
    {
        $this->container = $container;
        $this->action = $action;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->action->execute([]);
        return $this->container->ResultHandler->process($result);
    }
}
