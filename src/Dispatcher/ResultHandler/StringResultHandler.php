<?php

namespace Atom\Dispatcher\ResultHandler;

use Atom\Interfaces\IResultHandler;
use Psr\Http\Message\ResponseInterface;

class StringResultHandler implements IResultHandler
{
    use ResultHandlerTrait;

    public function isMatch($result): bool
    {
        return is_string($result);
    }

    public function process($result): ResponseInterface
    {
        $response = $this->getResponse();
        $response->getBody()->write($result);
        return $response;
    }
}
