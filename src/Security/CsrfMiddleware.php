<?php

declare(strict_types=1);

namespace Atom\Security;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ["GET", "HEAD", "OPTIONS"];

    public function __construct(private CsrfTokenManagerInterface $tokens)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $token = $request->headers()->get(CsrfTokenManagerInterface::HEADER_NAME);
        if ($token === null || $token === "") {
            $token = $request->post()->string(CsrfTokenManagerInterface::FIELD_NAME);
        }

        if ($this->tokens->validate($token)) {
            return $handler->handle($request);
        }

        return (new Response())
            ->status(403)
            ->header("Content-Type", "text/plain; charset=utf-8")
            ->header("Cache-Control", "no-store")
            ->content("Invalid CSRF token.");
    }
}
