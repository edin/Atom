<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Psr\Http\Message\ResponseInterface;

class ResultHandler
{
    private Container $container;
    private ResultHandlerRegistry $resultHandlerRegistry;

    public function __construct(Container $container, ResultHandlerRegistry $resultHandlerRegistry)
    {
        $this->container = $container;
        $this->resultHandlerRegistry = $resultHandlerRegistry;
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

        $handler = $this->resultHandlerRegistry->getHandler($result);

        if ($handler != null) {
            return $handler->process($result);
        }

        // Default result handler
        $response = $this->getResponse();
        $response->getBody()->write((string) $result);
        return $response;
    }
}
