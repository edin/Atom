<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Http\Response;

interface ResponseEmitterInterface
{
    public function emit(Response $response): void;
}
