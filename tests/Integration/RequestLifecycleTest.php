<?php

declare(strict_types=1);

namespace Atom\Tests\Integration;

use Atom\Application;
use Atom\Config\Config;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Http\Cookie;
use Atom\Http\CorsMiddleware;
use Atom\Http\CorsOptions;
use Atom\Http\MiddlewareRegistry;
use Atom\Http\Request;
use Atom\Http\RequestIdMiddleware;
use Atom\Http\RequestIdOptions;
use Atom\Http\Response;
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Hydrator\Attributes\FromRoute;
use Atom\Modules\ErrorPages\ErrorPagesOptions;
use Atom\Router\Route;
use Atom\Security\CsrfMiddleware;
use Atom\Security\CsrfTokenManagerInterface;
use Atom\Security\SecurityHeadersMiddleware;
use Atom\Security\SecurityHeadersOptions;
use Atom\Session\ArraySession;
use Atom\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RequestLifecycleTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Route::clearRouter();
    }

    public function testSuccessfulRequestRunsThroughTheCompleteLifecycle(): void
    {
        $app = new LifecycleApplication();

        $response = $app->handle(new Request(
            "GET",
            "/users/42",
            queryParams: ["name" => " Atom "],
            headers: [
                "Origin" => "https://client.example.com",
                "X-Request-Id" => "integration-request-123",
            ]
        ));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame([
            "id" => 42,
            "name" => "Atom",
            "requestId" => "integration-request-123",
        ], $this->json($response));
        $this->assertSame("application/json", $response->headers()->get("Content-Type"));
        $this->assertSame("integration-request-123", $response->headers()->get("X-Request-Id"));
        $this->assertSame("https://client.example.com", $response->headers()->get("Access-Control-Allow-Origin"));
        $this->assertSame("nosniff", $response->headers()->get("X-Content-Type-Options"));
        $this->assertSame(
            ["visited=yes; Path=/; HttpOnly; SameSite=Lax"],
            $response->headers()->all("Set-Cookie")
        );
    }

    public function testMissingRouteUsesTheDefaultJsonErrorPageThroughMiddleware(): void
    {
        $response = (new LifecycleApplication())->handle(new Request(
            "GET",
            "/missing",
            headers: [
                "Accept" => "application/json",
                "Origin" => "https://client.example.com",
            ]
        ));

        $body = $this->json($response);

        $this->assertSame(404, $response->getStatus());
        $this->assertSame("Page not found", $body["error"]["title"]);
        $this->assertSame("no-store", $response->headers()->get("Cache-Control"));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $response->headers()->get("X-Request-Id", "") ?? "");
        $this->assertSame("nosniff", $response->headers()->get("X-Content-Type-Options"));
        $this->assertSame("https://client.example.com", $response->headers()->get("Access-Control-Allow-Origin"));
    }

    public function testUnhandledExceptionBecomesSafeProductionErrorResponse(): void
    {
        $response = (new LifecycleApplication())->handle(new Request(
            "GET",
            "/explode",
            headers: ["Accept" => "application/json"]
        ));

        $body = $this->json($response);

        $this->assertSame(500, $response->getStatus());
        $this->assertSame("Something went wrong", $body["error"]["title"]);
        $this->assertArrayNotHasKey("debug", $body["error"]);
        $this->assertStringNotContainsString("sensitive details", $response->getContent());
        $this->assertSame("nosniff", $response->headers()->get("X-Content-Type-Options"));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $response->headers()->get("X-Request-Id", "") ?? "");
    }

    public function testSessionBackedCsrfProtectionPersistsAcrossRequests(): void
    {
        $app = new LifecycleApplication();

        $tokenResponse = $app->handle(new Request("GET", "/csrf"));
        $token = $this->json($tokenResponse)["token"];

        $rejected = $app->handle(new Request("POST", "/protected"));
        $accepted = $app->handle(new Request(
            "POST",
            "/protected",
            headers: [CsrfTokenManagerInterface::HEADER_NAME => $token]
        ));

        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame(403, $rejected->getStatus());
        $this->assertSame("Invalid CSRF token.", $rejected->getContent());
        $this->assertSame(["saved" => true, "count" => 1], $this->json($accepted));
        $this->assertSame(1, $app->session->get("saved"));
    }

    public function testCorsPreflightShortCircuitsBeforeRouteMatching(): void
    {
        $response = (new LifecycleApplication())->handle(new Request(
            "OPTIONS",
            "/not-a-route",
            headers: [
                "Origin" => "https://client.example.com",
                "Access-Control-Request-Method" => "POST",
                "Access-Control-Request-Headers" => "Content-Type, X-CSRF-Token",
            ]
        ));

        $this->assertSame(204, $response->getStatus());
        $this->assertSame("", $response->getContent());
        $this->assertSame("https://client.example.com", $response->headers()->get("Access-Control-Allow-Origin"));
        $this->assertSame("GET, POST, OPTIONS", $response->headers()->get("Access-Control-Allow-Methods"));
        $this->assertSame("Content-Type, X-CSRF-Token", $response->headers()->get("Access-Control-Allow-Headers"));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $response->headers()->get("X-Request-Id", "") ?? "");
    }

    /** @return array<string, mixed> */
    private function json(Response $response): array
    {
        $decoded = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        return $decoded;
    }
}

final class LifecycleApplication extends Application
{
    public readonly ArraySession $session;

    public function __construct()
    {
        $this->session = new ArraySession(id: "integration-session");
        parent::__construct();
    }

    protected function configure(Config $config): void
    {
        $config
            ->set(new ErrorPagesOptions(debug: false))
            ->set(new RequestIdOptions(trustIncoming: true))
            ->set(new CorsOptions(
                allowedOrigins: "https://client.example.com",
                allowedMethods: "GET, POST, OPTIONS",
                allowedHeaders: "Content-Type, X-CSRF-Token"
            ))
            ->set(new SecurityHeadersOptions());
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(new LifecycleSessionProvider($this->session));
    }

    protected function middlewares(MiddlewareRegistry $middlewares): void
    {
        $middlewares
            ->add(RequestIdMiddleware::class)
            ->add(CorsMiddleware::class)
            ->add(SecurityHeadersMiddleware::class)
            ->add(CsrfMiddleware::class);
    }

    protected function bootstrap(Injector $injector): void
    {
        Route::get("/users/{id}", function (
            LifecycleUserInput $input,
            Request $request,
            Response $response
        ): array {
            $response->cookie(Cookie::create("visited", "yes"));

            return [
                "id" => $input->id,
                "name" => $input->name,
                "requestId" => $request->headers()->get("X-Request-Id"),
            ];
        });

        Route::get("/csrf", static fn(CsrfTokenManagerInterface $tokens): array => [
            "token" => $tokens->token(),
        ]);

        Route::post("/protected", static function (SessionInterface $session): array {
            $count = (int) $session->get("saved", 0) + 1;
            $session->put("saved", $count);

            return ["saved" => true, "count" => $count];
        });

        Route::get("/explode", static function (): never {
            throw new RuntimeException("sensitive details");
        });
    }
}

final readonly class LifecycleSessionProvider implements ServiceProviderInterface
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function register(Bindings $bindings): void
    {
        $bindings->value(SessionInterface::class, $this->session);
    }
}

#[Dto]
final readonly class LifecycleUserInput
{
    public function __construct(
        #[FromRoute]
        public int $id,
        #[FromQuery]
        public string $name
    ) {
    }
}
