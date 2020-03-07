<?php

namespace Atom;

use Atom\Container\Container;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestResponseServices
{
    public function configureServices(Container $container)
    {
        $container->bind(ServerRequestInterface::class)->toFactory(function () {
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $creator = new \Nyholm\Psr7Server\ServerRequestCreator($factory, $factory, $factory, $factory);
            $serverRequest = $creator->fromGlobals();
            return $serverRequest;
        })->withName("Request");

        $container->bind(ResponseInterface::class)->toFactory(function () {
            $factory = new Psr17Factory();
            return $factory->createResponse();
        })->withName("Response");
    }
}
