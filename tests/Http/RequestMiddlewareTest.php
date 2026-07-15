<?php

declare(strict_types=1);

namespace Atom\Tests\Http;

use Atom\Config\Config;
use Atom\Http\Request;
use Atom\Http\RequestBodyLimitMiddleware;
use Atom\Http\RequestBodyLimitOptions;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\RequestIdMiddleware;
use Atom\Http\RequestIdOptions;
use Atom\Http\Response;
use PHPUnit\Framework\TestCase;

final class RequestMiddlewareTest extends TestCase
{
    public function testRequestIdUsesValidIncomingIdDownstreamAndOnResponse(): void
    {
        $handler = new RequestMiddlewareTestHandler();
        $response = (new RequestIdMiddleware(new RequestIdOptions()))->process(
            new Request("GET", "/", headers: ["X-Request-Id" => "edge_123"]),
            $handler
        );

        $this->assertSame("edge_123", $handler->request?->headers()->get("X-Request-Id"));
        $this->assertSame("edge_123", $response->headers()->get("X-Request-Id"));
    }

    public function testRequestIdReplacesInvalidOrUntrustedIncomingId(): void
    {
        $invalidHandler = new RequestMiddlewareTestHandler();
        $untrustedHandler = new RequestMiddlewareTestHandler();
        $middleware = new RequestIdMiddleware(new RequestIdOptions(maxLength: 32));
        $middleware->process(
            new Request("GET", "/", headers: ["X-Request-Id" => str_repeat("a", 33)]),
            $invalidHandler
        );
        (new RequestIdMiddleware(new RequestIdOptions(trustIncoming: false)))->process(
            new Request("GET", "/", headers: ["X-Request-Id" => "valid-id"]),
            $untrustedHandler
        );

        $invalidId = $invalidHandler->request?->headers()->get("X-Request-Id");
        $untrustedId = $untrustedHandler->request?->headers()->get("X-Request-Id");
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $invalidId ?? "");
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $untrustedId ?? "");
        $this->assertNotSame("valid-id", $untrustedId);
    }

    public function testRequestIdPreservesHeaderExplicitlySetByResponse(): void
    {
        $handler = new RequestMiddlewareTestHandler((new Response())->header("X-Request-Id", "response-id"));
        $response = (new RequestIdMiddleware(new RequestIdOptions()))->process(
            new Request("GET", "/"),
            $handler
        );

        $this->assertSame("response-id", $response->headers()->get("X-Request-Id"));
    }

    public function testBodyLimitRejectsDeclaredOrActualOversizedBodies(): void
    {
        $middleware = new RequestBodyLimitMiddleware(new RequestBodyLimitOptions(5));
        $declaredHandler = new RequestMiddlewareTestHandler();
        $actualHandler = new RequestMiddlewareTestHandler();

        $declared = $middleware->process(
            new Request("POST", "/", headers: ["Content-Length" => "6"]),
            $declaredHandler
        );
        $actual = $middleware->process(
            new Request("POST", "/", body: "123456"),
            $actualHandler
        );

        $this->assertSame(413, $declared->getStatus());
        $this->assertSame("Content Too Large", $declared->getReasonPhrase());
        $this->assertSame(413, $actual->getStatus());
        $this->assertFalse($declaredHandler->called);
        $this->assertFalse($actualHandler->called);
    }

    public function testBodyLimitRejectsAmbiguousContentLength(): void
    {
        $handler = new RequestMiddlewareTestHandler();
        $response = (new RequestBodyLimitMiddleware(new RequestBodyLimitOptions(100)))->process(
            new Request("POST", "/", headers: ["Content-Length" => ["10", "20"]]),
            $handler
        );

        $this->assertSame(400, $response->getStatus());
        $this->assertSame("Invalid Content-Length header.", $response->getContent());
        $this->assertFalse($handler->called);
    }

    public function testBodyLimitAllowsRequestAtLimitAndCanBeDisabled(): void
    {
        $limitedHandler = new RequestMiddlewareTestHandler();
        $disabledHandler = new RequestMiddlewareTestHandler();
        $limited = (new RequestBodyLimitMiddleware(new RequestBodyLimitOptions(5)))->process(
            new Request("POST", "/", body: "12345", headers: ["Content-Length" => "0005"]),
            $limitedHandler
        );
        $disabled = (new RequestBodyLimitMiddleware(new RequestBodyLimitOptions(0)))->process(
            new Request("POST", "/", body: str_repeat("x", 100)),
            $disabledHandler
        );

        $this->assertSame("ok", $limited->getContent());
        $this->assertSame("ok", $disabled->getContent());
        $this->assertTrue($limitedHandler->called);
        $this->assertTrue($disabledHandler->called);
    }

    public function testRequestMiddlewareOptionsHydrateFromEnvironment(): void
    {
        $config = Config::fromEnv([
            "REQUEST_ID_HEADER_NAME" => "X-Correlation-Id",
            "REQUEST_ID_TRUST_INCOMING" => "false",
            "REQUEST_ID_MAX_LENGTH" => "64",
            "REQUEST_BODY_MAX_BYTES" => "2048",
        ]);
        $requestId = $config->options(RequestIdOptions::class);
        $body = $config->options(RequestBodyLimitOptions::class);

        $this->assertSame("X-Correlation-Id", $requestId->headerName);
        $this->assertFalse($requestId->trustIncoming);
        $this->assertSame(64, $requestId->maxLength);
        $this->assertSame(2048, $body->maxBytes);
    }
}

final class RequestMiddlewareTestHandler implements RequestHandlerInterface
{
    public bool $called = false;
    public ?Request $request = null;

    public function __construct(private ?Response $response = null)
    {
    }

    public function handle(Request $request): Response
    {
        $this->called = true;
        $this->request = $request;
        return $this->response ?? (new Response())->content("ok");
    }
}
