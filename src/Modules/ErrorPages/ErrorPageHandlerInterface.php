<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

use Atom\Http\Request;
use Atom\Http\Response;
use Throwable;

interface ErrorPageHandlerInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function forStatus(int $status, Request $request, array $headers = []): Response;

    public function forException(Throwable $exception, Request $request): Response;
}
