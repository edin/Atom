<?php

declare(strict_types=1);

namespace Atom\Identity;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final readonly class AuthenticateMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthenticatorInterface $authenticator)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->authenticator->check()) {
            return $handler->handle($request);
        }

        return (new Response())
            ->status(401)
            ->header("Content-Type", "text/plain; charset=utf-8")
            ->header("Cache-Control", "no-store")
            ->content("Authentication required.");
    }
}
