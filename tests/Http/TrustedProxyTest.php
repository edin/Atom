<?php

declare(strict_types=1);

namespace Atom\Tests\Http;

use Atom\Config\Config;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Http\TrustedProxyMiddleware;
use Atom\Http\TrustedProxyOptions;
use PHPUnit\Framework\TestCase;

final class TrustedProxyTest extends TestCase
{
    public function testUntrustedPeerCannotSpoofForwardedMetadata(): void
    {
        $handler = new TrustedProxyTestHandler();
        $middleware = new TrustedProxyMiddleware(new TrustedProxyOptions("10.0.0.0/8"));
        $request = new Request("GET", "/", serverParams: [
            "REMOTE_ADDR" => "203.0.113.20",
            "HTTP_HOST" => "internal.local",
        ], headers: [
            "X-Forwarded-For" => "198.51.100.7",
            "X-Forwarded-Proto" => "https",
            "X-Forwarded-Host" => "example.com",
        ]);

        $middleware->process($request, $handler);

        $this->assertSame($request, $handler->request);
        $this->assertSame("203.0.113.20", $handler->request?->getClientIp());
        $this->assertSame("http", $handler->request?->getScheme());
        $this->assertSame("internal.local", $handler->request?->getHost());
    }

    public function testTrustedPeerNormalizesForwardedMetadata(): void
    {
        $handler = new TrustedProxyTestHandler();
        $middleware = new TrustedProxyMiddleware(new TrustedProxyOptions("10.0.0.0/8"));
        $request = new Request("GET", "/", serverParams: [
            "REMOTE_ADDR" => "10.0.0.4",
            "SERVER_PORT" => 80,
        ], headers: [
            "X-Forwarded-For" => "198.51.100.7",
            "X-Forwarded-Proto" => "https",
            "X-Forwarded-Host" => "app.example.com",
        ]);

        $middleware->process($request, $handler);

        $this->assertNotSame($request, $handler->request);
        $this->assertSame("198.51.100.7", $handler->request?->getClientIp());
        $this->assertSame("https", $handler->request?->getScheme());
        $this->assertTrue($handler->request?->isSecure());
        $this->assertSame("app.example.com", $handler->request?->getHost());
        $this->assertSame("app.example.com", $handler->request?->headers()->get("Host"));
        $this->assertSame(443, $handler->request?->server()->int("SERVER_PORT"));
    }

    public function testClientIpWalksBackAcrossTrustedProxyChain(): void
    {
        $handler = new TrustedProxyTestHandler();
        $middleware = new TrustedProxyMiddleware(new TrustedProxyOptions("10.0.0.0/8, 192.168.0.0/16"));
        $request = new Request("GET", "/", serverParams: ["REMOTE_ADDR" => "10.0.0.4"], headers: [
            "X-Forwarded-For" => "198.51.100.7, 192.168.1.8",
        ]);

        $middleware->process($request, $handler);

        $this->assertSame("198.51.100.7", $handler->request?->getClientIp());
    }

    public function testStandardForwardedHeaderTakesPrecedence(): void
    {
        $handler = new TrustedProxyTestHandler();
        $middleware = new TrustedProxyMiddleware(new TrustedProxyOptions("2001:db8::/32"));
        $request = new Request("GET", "/", serverParams: ["REMOTE_ADDR" => "2001:db8::10"], headers: [
            "Forwarded" => 'for="[2001:4860::7]";proto=https;host="example.org:8443"',
            "X-Forwarded-For" => "198.51.100.9",
        ]);

        $middleware->process($request, $handler);

        $this->assertSame("2001:4860::7", $handler->request?->getClientIp());
        $this->assertSame("https", $handler->request?->getScheme());
        $this->assertSame("example.org:8443", $handler->request?->getHost());
        $this->assertSame(8443, $handler->request?->server()->int("SERVER_PORT"));
    }

    public function testOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "TRUSTED_PROXIES" => "127.0.0.1,10.0.0.0/8",
        ])->options(TrustedProxyOptions::class);

        $this->assertSame("127.0.0.1,10.0.0.0/8", $options->proxies);
    }

}

final class TrustedProxyTestHandler implements RequestHandlerInterface
{
    public ?Request $request = null;

    public function handle(Request $request): Response
    {
        $this->request = $request;
        return (new Response())->content("ok");
    }
}
