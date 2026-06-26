<?php

declare(strict_types=1);

namespace Atom\Dispatcher\ResultHandler;

use Atom\Http\Response;

abstract class AbstractResultHandler
{
    public function __construct(protected Response $response)
    {
    }

    protected function getResponse(): Response
    {
        return $this->response;
    }
}
