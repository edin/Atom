<?php

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Atom\Dispatcher\ResultHandler\ArrayResultHandler;
use Atom\Dispatcher\ResultHandler\StringResultHandler;
use Atom\Dispatcher\ResultHandler\ViewInfoResultHandler;
use Psr\Http\Message\ResponseInterface;

class ResultHandler
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    private function getResponse()
    {
        return $this->container->Response;
    }

    public function process($result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        // TODO: Provide registry for result handlers
        $resultHandlers = [
            new ViewInfoResultHandler($this->container),
            new StringResultHandler($this->container),
            new ArrayResultHandler($this->container),
        ];

        foreach ($resultHandlers as $resultHandler) {
            if ($resultHandler->isMatch($result)) {
                return $resultHandler->process($result);
            }
        }

        // Default result handler
        $response = $this->getResponse();
        $response->getBody()->write((string) $result);
        return $response;
    }
}
