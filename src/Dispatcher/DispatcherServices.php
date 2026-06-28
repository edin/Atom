<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Hydrator\DtoTypeFactory;
use Atom\Router\Router;

final class DispatcherServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(Injector::class)
            ->toFactory(fn(Injector $injector) => $injector)
            ->singleton();

        $bindings->bind(InjectionContext::class)
            ->toFactory(fn(Injector $injector, InjectionContext $context) => $context)
            ->scoped();

        $bindings->bind(Router::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(Request::class)
            ->toFactory(fn() => Request::fromGlobals())
            ->scoped();

        $bindings->bind(Response::class)
            ->toFactory(fn() => new Response())
            ->scoped();

        $bindings->bind(ResponseEmitterInterface::class)
            ->to(ResponseEmitter::class)
            ->singleton();

        $bindings->bind(ResultHandlerRegistry::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(ResultConverter::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(RouteInvoker::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(Dispatcher::class)
            ->toSelf()
            ->scoped();

        $bindings->bind(RequestHandlerInterface::class)
            ->toExisting(Dispatcher::class);

        $bindings->addTypeFactory(DtoTypeFactory::create());
    }
}
