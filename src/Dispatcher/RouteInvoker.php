<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Router\Action;
use Atom\Router\MatchedRoute;

final readonly class RouteInvoker
{
    public function __construct(private Injector $injector)
    {
    }

    public function invoke(MatchedRoute $route, InjectionContext $context): mixed
    {
        $action = new Action($this->injector, $route, $context);
        return $action->execute();
    }
}
