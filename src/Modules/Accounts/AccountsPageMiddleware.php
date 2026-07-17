<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final readonly class AccountsPageMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        if (!$response->headers()->has("Content-Type")) {
            $response->header("Content-Type", "text/html; charset=utf-8");
        }
        if (!$response->headers()->has("Cache-Control")) {
            $response->header("Cache-Control", "no-store");
        }

        return $response;
    }
}
