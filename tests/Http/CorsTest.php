<?php

declare(strict_types=1);

namespace Atom\Tests\Http;

use Atom\Config\Config;
use Atom\Http\CorsMiddleware;
use Atom\Http\CorsOptions;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CorsTest extends TestCase
{
    public function testAllowedActualRequestReceivesCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(new CorsOptions(
            allowedOrigins: "https://app.example.com",
            exposedHeaders: "X-Request-Id",
            allowCredentials: true
        ));
        $response = $middleware->process(
            new Request("GET", "/", headers: ["Origin" => "https://app.example.com"]),
            new CorsTestHandler()
        );

        $this->assertSame("ok", $response->getContent());
        $this->assertSame("https://app.example.com", $response->headers()->get("Access-Control-Allow-Origin"));
        $this->assertSame("true", $response->headers()->get("Access-Control-Allow-Credentials"));
        $this->assertSame("X-Request-Id", $response->headers()->get("Access-Control-Expose-Headers"));
        $this->assertSame("Origin", $response->headers()->get("Vary"));
    }

    public function testDisallowedActualRequestContinuesWithoutCorsHeaders(): void
    {
        $response = (new CorsMiddleware(new CorsOptions(allowedOrigins: "https://app.example.com")))->process(
            new Request("GET", "/", headers: ["Origin" => "https://other.example.com"]),
            new CorsTestHandler()
        );

        $this->assertSame("ok", $response->getContent());
        $this->assertFalse($response->headers()->has("Access-Control-Allow-Origin"));
    }

    public function testAllowedPreflightReturnsNoContentWithoutCallingHandler(): void
    {
        $handler = new CorsTestHandler();
        $middleware = new CorsMiddleware(new CorsOptions(
            allowedOrigins: "https://app.example.com",
            allowedMethods: "GET,POST",
            allowedHeaders: "Content-Type,X-CSRF-Token",
            maxAge: 600
        ));
        $response = $middleware->process(new Request("OPTIONS", "/api", headers: [
            "Origin" => "https://app.example.com",
            "Access-Control-Request-Method" => "POST",
            "Access-Control-Request-Headers" => "Content-Type, X-CSRF-Token",
        ]), $handler);

        $this->assertSame(204, $response->getStatus());
        $this->assertFalse($handler->called);
        $this->assertSame("GET, POST", $response->headers()->get("Access-Control-Allow-Methods"));
        $this->assertSame("Content-Type, X-CSRF-Token", $response->headers()->get("Access-Control-Allow-Headers"));
        $this->assertSame("600", $response->headers()->get("Access-Control-Max-Age"));
        $this->assertSame(
            "Origin, Access-Control-Request-Method, Access-Control-Request-Headers",
            $response->headers()->get("Vary")
        );
    }

    public function testInvalidPreflightIsRejected(): void
    {
        $handler = new CorsTestHandler();
        $response = (new CorsMiddleware(new CorsOptions(
            allowedOrigins: "https://app.example.com",
            allowedMethods: "GET",
            allowedHeaders: "Content-Type"
        )))->process(new Request("OPTIONS", "/api", headers: [
            "Origin" => "https://app.example.com",
            "Access-Control-Request-Method" => "DELETE",
        ]), $handler);

        $this->assertSame(403, $response->getStatus());
        $this->assertFalse($handler->called);
        $this->assertFalse($response->headers()->has("Access-Control-Allow-Origin"));
    }

    public function testWildcardOriginWorksWithoutCredentials(): void
    {
        $response = (new CorsMiddleware(new CorsOptions(allowedOrigins: "*")))->process(
            new Request("GET", "/", headers: ["Origin" => "https://any.example"]),
            new CorsTestHandler()
        );

        $this->assertSame("*", $response->headers()->get("Access-Control-Allow-Origin"));
        $this->assertFalse($response->headers()->has("Vary"));
    }

    public function testWildcardOriginWithCredentialsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CorsMiddleware(new CorsOptions(allowedOrigins: "*", allowCredentials: true));
    }

    public function testOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "CORS_ALLOWED_ORIGINS" => "https://app.example.com",
            "CORS_ALLOW_CREDENTIALS" => "true",
            "CORS_MAX_AGE" => "300",
        ])->options(CorsOptions::class);

        $this->assertSame("https://app.example.com", $options->allowedOrigins);
        $this->assertTrue($options->allowCredentials);
        $this->assertSame(300, $options->maxAge);
    }
}

final class CorsTestHandler implements RequestHandlerInterface
{
    public bool $called = false;

    public function handle(Request $request): Response
    {
        $this->called = true;
        return (new Response())->content("ok");
    }
}
