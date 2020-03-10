<?php

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Router\Action;
use Atom\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteHandler implements RequestHandlerInterface
{
    private $container;
    private $route;

    public function __construct(Container $container, Route $route)
    {
        $this->container = $container;
        $this->route = $route;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $action = new Action($this->container, $this->route);
        $result = $this->runActionFilters($action);

        if ($result === null) {
            $result = $action->execute([]);
        }

        return $this->container->ResultHandler->process($result);
    }

    private function runActionFilters(Action $action)
    {
        $context = $this->container->RequestScope;
        $filters = $action->getRoute()->getActionFilters();
        foreach ($filters as $filter) {
            $filterInstance = $this->getFilter($context, $filter);
            $result = $filterInstance->filter($action);
            if ($filter !== null) {
                return $result;
            }
        }
        return null;
    }

    private function getFilter($context, $filter)
    {
        if (is_string($filter)) {
            return $this->container->resolveInContext($context, $filter);
        } elseif (is_object($filter)) {
            return $filter;
        }
        throw new \RuntimeException("Can't initialize middleware, unsupported definition.");
    }
}
