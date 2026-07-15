<?php

declare(strict_types=1);

namespace Atom\Tests\Http;

use Atom\Config\Config;
use Atom\Dispatcher\MiddlewarePipeline;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Http\TrustedHostMiddleware;
use Atom\Http\TrustedHostOptions;
use Atom\Http\TrustedProxyMiddleware;
use Atom\Http\TrustedProxyOptions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TrustedHostTest extends TestCase
{
    public function testExactHostIsAllowedCaseInsensitivelyWithTrailingDot(): void
    {
        $handler = new TrustedHostTestHandler();
        $response = (new TrustedHostMiddleware(new TrustedHostOptions("example.com")))->process(
            new Request("GET", "/", headers: ["Host" => "EXAMPLE.COM."]),
            $handler
        );

        $this->assertSame("ok", $response->getContent());
        $this->assertTrue($handler->called);
    }

    public function testWildcardAllowsSubdomainsButNotApex(): void
    {
        $middleware = new TrustedHostMiddleware(new TrustedHostOptions("*.example.com"));
        $subdomain = new TrustedHostTestHandler();
        $apex = new TrustedHostTestHandler();

        $allowed = $middleware->process(
            new Request("GET", "/", headers: ["Host" => "api.eu.example.com"]),
            $subdomain
        );
        $denied = $middleware->process(
            new Request("GET", "/", headers: ["Host" => "example.com"]),
            $apex
        );

        $this->assertSame(200, $allowed->getStatus());
        $this->assertSame(400, $denied->getStatus());
        $this->assertTrue($subdomain->called);
        $this->assertFalse($apex->called);
    }

    public function testExplicitPortIsRestrictedWhileHostWithoutPortAllowsAnyPort(): void
    {
        $restricted = new TrustedHostMiddleware(new TrustedHostOptions("example.com:8443"));
        $unrestricted = new TrustedHostMiddleware(new TrustedHostOptions("example.org"));

        $this->assertSame(200, $restricted->process(
            new Request("GET", "/", headers: ["Host" => "example.com:8443"]),
            new TrustedHostTestHandler()
        )->getStatus());
        $this->assertSame(400, $restricted->process(
            new Request("GET", "/", headers: ["Host" => "example.com:443"]),
            new TrustedHostTestHandler()
        )->getStatus());
        $this->assertSame(200, $unrestricted->process(
            new Request("GET", "/", headers: ["Host" => "example.org:8080"]),
            new TrustedHostTestHandler()
        )->getStatus());
    }

    public function testIpv4AndBracketedIpv6HostsAreSupported(): void
    {
        $middleware = new TrustedHostMiddleware(new TrustedHostOptions("127.0.0.1,[::1]:8080"));

        $this->assertSame(200, $middleware->process(
            new Request("GET", "/", headers: ["Host" => "127.0.0.1"]),
            new TrustedHostTestHandler()
        )->getStatus());
        $this->assertSame(200, $middleware->process(
            new Request("GET", "/", headers: ["Host" => "[::1]:8080"]),
            new TrustedHostTestHandler()
        )->getStatus());
    }

    public function testMissingMalformedOrUntrustedHostIsRejected(): void
    {
        $middleware = new TrustedHostMiddleware(new TrustedHostOptions("example.com"));

        foreach (["", "evil.example", "example.com/path", "example.com\r\nX-Evil: yes"] as $host) {
            $handler = new TrustedHostTestHandler();
            $response = $middleware->process(
                new Request("GET", "/", headers: $host === "" ? [] : ["Host" => $host]),
                $handler
            );
            $this->assertSame(400, $response->getStatus());
            $this->assertFalse($handler->called);
        }
    }

    public function testEmptyConfigurationDisablesValidation(): void
    {
        $handler = new TrustedHostTestHandler();
        $response = (new TrustedHostMiddleware(new TrustedHostOptions()))->process(
            new Request("GET", "/"),
            $handler
        );

        $this->assertSame(200, $response->getStatus());
        $this->assertTrue($handler->called);
    }

    public function testTrustedProxyHostIsValidatedWhenMiddlewareOrderIsCorrect(): void
    {
        $pipeline = new MiddlewarePipeline([
            new TrustedProxyMiddleware(new TrustedProxyOptions("10.0.0.0/8")),
            new TrustedHostMiddleware(new TrustedHostOptions("public.example.com")),
        ], new TrustedHostTestHandler());

        $response = $pipeline->handle(new Request("GET", "/", serverParams: [
            "REMOTE_ADDR" => "10.0.0.4",
            "HTTP_HOST" => "internal.local",
        ], headers: ["X-Forwarded-Host" => "public.example.com"]));

        $this->assertSame(200, $response->getStatus());
    }

    public function testInvalidTrustedHostPatternIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TrustedHostMiddleware(new TrustedHostOptions("https://example.com"));
    }

    public function testOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "TRUSTED_HOSTS" => "example.com,*.example.org",
        ])->options(TrustedHostOptions::class);

        $this->assertSame("example.com,*.example.org", $options->hosts);
    }
}

final class TrustedHostTestHandler implements RequestHandlerInterface
{
    public bool $called = false;

    public function handle(Request $request): Response
    {
        $this->called = true;
        return (new Response())->content("ok");
    }
}
