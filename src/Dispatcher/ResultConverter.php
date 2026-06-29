<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Response;

final readonly class ResultConverter
{
    public function __construct(
        private Injector $injector,
        private Response $response,
        private ResultHandlerRegistry $handlers
    ) {
    }

    public function toResponse(mixed $result, ?InjectionContext $context = null): Response
    {
        $context ??= new InjectionContext();

        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof ResponseResultInterface) {
            return $result->toResponse($this->injector, $context);
        }

        $handler = $this->handlers->getHandler($result, $context);
        if ($handler !== null) {
            return $handler->process($result);
        }

        return $this->response->write((string) $result);
    }
}
