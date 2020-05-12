<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Router\Action;
use Atom\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteHandler implements RequestHandlerInterface
{
    private Container $container;
    private Route $route;

    public function __construct(Container $container, Route $route)
    {
        $this->container = $container;
        $this->route = $route;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $action = new Action($this->container, $this->route);

        $result = $action->execute([]);

        return $this->container->ResultHandler->process($result);
    }
}
