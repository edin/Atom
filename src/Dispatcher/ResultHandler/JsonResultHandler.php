<?php

declare(strict_types=1);

namespace Atom\Dispatcher\ResultHandler;

use Atom\Dispatcher\ResultHandlerInterface;
use Atom\Http\Response;
use JsonSerializable;
use stdClass;
use Stringable;

class JsonResultHandler extends AbstractResultHandler implements ResultHandlerInterface
{
    public function isMatch(mixed $result): bool
    {
        return is_array($result)
            || $result instanceof JsonSerializable
            || $result instanceof stdClass
            || (is_object($result) && !$result instanceof Stringable);
    }

    public function process(mixed $result): Response
    {
        return $this->getResponse()->json($result);
    }
}
