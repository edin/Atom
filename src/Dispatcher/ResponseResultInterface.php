<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Response;

interface ResponseResultInterface
{
    public function toResponse(Injector $injector, InjectionContext $context): Response;
}
