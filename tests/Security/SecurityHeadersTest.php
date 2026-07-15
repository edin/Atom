<?php

declare(strict_types=1);

namespace Atom\Tests\Security;

use Atom\Config\Config;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Security\SecurityHeadersMiddleware;
use Atom\Security\SecurityHeadersOptions;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersTest extends TestCase
{
    public function testMiddlewareAddsSafeDefaultsWithoutCspOrHsts(): void
    {
        $middleware = new SecurityHeadersMiddleware(new SecurityHeadersOptions());
        $response = $middleware->process(
            new Request("GET", "/"),
            new SecurityHeadersTestHandler()
        );

        $this->assertSame("nosniff", $response->headers()->get("X-Content-Type-Options"));
        $this->assertSame("SAMEORIGIN", $response->headers()->get("X-Frame-Options"));
        $this->assertSame("strict-origin-when-cross-origin", $response->headers()->get("Referrer-Policy"));
        $this->assertSame(
            "camera=(), microphone=(), geolocation=()",
            $response->headers()->get("Permissions-Policy")
        );
        $this->assertFalse($response->headers()->has("Content-Security-Policy"));
        $this->assertFalse($response->headers()->has("Strict-Transport-Security"));
    }

    public function testMiddlewareAddsConfiguredCspAndHstsOnlyOnHttps(): void
    {
        $options = new SecurityHeadersOptions(
            contentSecurityPolicy: "default-src 'self'",
            contentSecurityPolicyReportOnly: "script-src 'self'",
            hstsMaxAge: 31536000,
            hstsIncludeSubDomains: true,
            hstsPreload: true
        );
        $middleware = new SecurityHeadersMiddleware($options);

        $secure = $middleware->process(
            new Request("GET", "/", serverParams: ["HTTPS" => "on"]),
            new SecurityHeadersTestHandler()
        );
        $insecure = $middleware->process(
            new Request("GET", "/"),
            new SecurityHeadersTestHandler()
        );

        $this->assertSame("default-src 'self'", $secure->headers()->get("Content-Security-Policy"));
        $this->assertSame(
            "script-src 'self'",
            $secure->headers()->get("Content-Security-Policy-Report-Only")
        );
        $this->assertSame(
            "max-age=31536000; includeSubDomains; preload",
            $secure->headers()->get("Strict-Transport-Security")
        );
        $this->assertFalse($insecure->headers()->has("Strict-Transport-Security"));
    }

    public function testMiddlewarePreservesResponseSpecificHeaderAndAllowsDefaultsToBeDisabled(): void
    {
        $response = (new Response())->header("X-Frame-Options", "DENY");
        $middleware = new SecurityHeadersMiddleware(new SecurityHeadersOptions(
            noSniff: false,
            frameOptions: "SAMEORIGIN",
            referrerPolicy: "",
            permissionsPolicy: ""
        ));

        $result = $middleware->process(new Request("GET", "/"), new SecurityHeadersTestHandler($response));

        $this->assertSame("DENY", $result->headers()->get("X-Frame-Options"));
        $this->assertFalse($result->headers()->has("X-Content-Type-Options"));
        $this->assertFalse($result->headers()->has("Referrer-Policy"));
        $this->assertFalse($result->headers()->has("Permissions-Policy"));
    }

    public function testOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "SECURITY_HEADERS_NO_SNIFF" => "false",
            "SECURITY_HEADERS_FRAME_OPTIONS" => "DENY",
            "SECURITY_HEADERS_HSTS_MAX_AGE" => "600",
            "SECURITY_HEADERS_HSTS_INCLUDE_SUB_DOMAINS" => "false",
        ])->options(SecurityHeadersOptions::class);

        $this->assertFalse($options->noSniff);
        $this->assertSame("DENY", $options->frameOptions);
        $this->assertSame(600, $options->hstsMaxAge);
        $this->assertFalse($options->hstsIncludeSubDomains);
    }
}

final readonly class SecurityHeadersTestHandler implements RequestHandlerInterface
{
    public function __construct(private ?Response $response = null)
    {
    }

    public function handle(Request $request): Response
    {
        return $this->response ?? (new Response())->content("ok");
    }
}
