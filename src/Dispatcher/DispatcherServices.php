<?php

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Atom\Container\ResolutionContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class DispatcherServices
{
    public function configureServices(Container $container)
    {
        $container->bind(ServerRequestInterface::class)
            ->withName("Request")
            ->toFactory(function () {
                $factory = new Psr17Factory();
                $creator = new ServerRequestCreator($factory, $factory, $factory, $factory);
                $serverRequest = $creator->fromGlobals();
                return $serverRequest;
            });

        $container->bind(ResponseInterface::class)
            ->withName("Response")
            ->toFactory(function () {
                $factory = new Psr17Factory();
                return $factory->createResponse();
            });

        $container->bind(IResponseEmitter::class)
            ->withName("ResponseEmitter")
            ->to(ResponseEmitter::class)
            ->asShared();

        $container->bind(ResolutionContext::class)
            ->withName("RequestScope")
            ->toSelf()
            ->asShared();

        $container->Dispatcher = Dispatcher::class;
        $container->ResultHandler = ResultHandler::class;
    }
}
