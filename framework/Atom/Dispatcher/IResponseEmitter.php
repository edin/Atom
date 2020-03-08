<?php

namespace Atom\Dispatcher;

use Psr\Http\Message\ResponseInterface;

interface IResponseEmitter
{
    public function emit(ResponseInterface $response): void;
}
