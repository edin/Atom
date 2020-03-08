<?php

namespace Atom\Dispatcher;

use Atom\Container\Container;
use Atom\Dispatcher\ResultHandler\ArrayResultHandler;
use Atom\Dispatcher\ResultHandler\StringResultHandler;
use Atom\Dispatcher\ResultHandler\ViewInfoResultHandler;
use Atom\Interfaces\IResultHandler;

class ResultHandlerRegistry
{
    /** @var Container */
    private $container;

    /** @var array */
    private $handlers = [];

    public function __construct(Container $container, ?array $handlers = null)
    {
        $this->container = $container;
        $this->handlers = $handlers ?? [
            ViewInfoResultHandler::class,
            StringResultHandler::class,
            ArrayResultHandler::class,
        ];
    }

    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function setHandlers(array $handlers): void
    {
        $this->handlers = $handlers;
    }

    public function addHandler(string $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function getHandler($result): ?IResultHandler
    {
        foreach ($this->handlers as $handlerType) {
            $handler = $this->container->resolve($handlerType);
            if ($handler->isMatch($result)) {
                return $handler;
            }
        }
        return null;
    }
}
