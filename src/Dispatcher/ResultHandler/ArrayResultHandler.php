<?php

namespace Atom\Dispatcher\ResultHandler;

use Atom\Interfaces\IResultHandler;
use Psr\Http\Message\ResponseInterface;

class ArrayResultHandler implements IResultHandler
{
    use ResultHandlerTrait;

    public function isMatch($result): bool
    {
        return is_array($result) || is_object($result);
    }

    public function process($result): ResponseInterface
    {
        $response = $this->getResponse()->withAddedHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
