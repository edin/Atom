<?php

declare(strict_types=1);

namespace Atom\Http;

interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}
