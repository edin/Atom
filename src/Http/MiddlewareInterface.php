<?php

declare(strict_types=1);

namespace Atom\Http;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
