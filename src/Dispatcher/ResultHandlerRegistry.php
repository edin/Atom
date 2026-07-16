<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Dispatcher\ResultHandler\JsonResultHandler;
use Atom\Dispatcher\ResultHandler\StringResultHandler;

class ResultHandlerRegistry
{
    /** @var list<class-string<ResultHandlerInterface>> */
    private array $handlers = [];
    /** @var array<class-string<ResultHandlerInterface>, ResultHandlerInterface> */
    private array $instances = [];

    /** @param list<class-string<ResultHandlerInterface>>|null $handlers */
    public function __construct(private Injector $injector, ?array $handlers = null)
    {
        $this->handlers = $handlers ?? [
            StringResultHandler::class,
            JsonResultHandler::class,
        ];
    }

    /** @return list<class-string<ResultHandlerInterface>> */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /** @param list<class-string<ResultHandlerInterface>> $handlers */
    public function setHandlers(array $handlers): void
    {
        $this->handlers = $handlers;
    }

    /** @param class-string<ResultHandlerInterface> $handler */
    public function addHandler(string $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function getHandler(mixed $result, ?InjectionContext $context = null): ?ResultHandlerInterface
    {
        $context ??= new InjectionContext();

        foreach ($this->handlers as $handlerType) {
            $handler = $this->resolveHandler($handlerType, $context);
            if ($handler->isMatch($result)) {
                return $handler;
            }
        }
        return null;
    }

    /** @param class-string<ResultHandlerInterface> $handlerType */
    private function resolveHandler(string $handlerType, InjectionContext $context): ResultHandlerInterface
    {
        return $this->instances[$handlerType] ??= $this->injector->get($handlerType, $context);
    }
}
