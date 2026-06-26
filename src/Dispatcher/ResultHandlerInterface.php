<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Http\Response;

interface ResultHandlerInterface
{
    public function isMatch(mixed $result): bool;

    public function process(mixed $result): Response;
}
