<?php

declare(strict_types=1);

namespace Atom\Security;

use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use InvalidArgumentException;

final readonly class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private SecurityHeadersOptions $options)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        if ($this->options->noSniff) {
            $this->setIfMissing($response, "X-Content-Type-Options", "nosniff");
        }

        $this->setIfMissing($response, "X-Frame-Options", $this->options->frameOptions);
        $this->setIfMissing($response, "Referrer-Policy", $this->options->referrerPolicy);
        $this->setIfMissing($response, "Permissions-Policy", $this->options->permissionsPolicy);
        $this->setIfMissing($response, "Content-Security-Policy", $this->options->contentSecurityPolicy);
        $this->setIfMissing(
            $response,
            "Content-Security-Policy-Report-Only",
            $this->options->contentSecurityPolicyReportOnly
        );

        if ($request->isSecure() && $this->options->hstsMaxAge > 0) {
            $this->setIfMissing($response, "Strict-Transport-Security", $this->hsts());
        }

        return $response;
    }

    private function setIfMissing(Response $response, string $name, string $value): void
    {
        if ($value === "" || $response->headers()->has($name)) {
            return;
        }

        if (str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new InvalidArgumentException("Security header '{$name}' cannot contain line breaks.");
        }

        $response->header($name, $value);
    }

    private function hsts(): string
    {
        $value = "max-age=" . max(0, $this->options->hstsMaxAge);
        if ($this->options->hstsIncludeSubDomains) {
            $value .= "; includeSubDomains";
        }
        if ($this->options->hstsPreload) {
            $value .= "; preload";
        }

        return $value;
    }
}
