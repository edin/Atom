<?php

declare(strict_types=1);

namespace Atom\Dispatcher\ResultHandler;

use Atom\Dispatcher\ResultHandlerInterface;
use Atom\Http\Response;

class StringResultHandler extends AbstractResultHandler implements ResultHandlerInterface
{
    public function isMatch(mixed $result): bool
    {
        return is_string($result);
    }

    public function process(mixed $result): Response
    {
        $response = $this->getResponse();
        $response->write($result);
        return $response;
    }
}
