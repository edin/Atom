<?php

declare(strict_types=1);

namespace Atom\Tests\Module;

use Atom\Application;
use Atom\Cache\CacheInterface;
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Http\Request;
use Atom\Identity\IdentityInterface;
use Atom\Identity\IdentityProviderInterface;
use Atom\Module\ModuleRegistry;
use Atom\Modules\Accounts\Accounts;
use Atom\Modules\Accounts\AccountsOptions;
use Atom\Modules\Accounts\AccountsRedirects;
use Atom\Modules\Accounts\UI\Components\LogoutForm;
use Atom\Modules\Accounts\UI\Pages\LoginPage;
use Atom\Page\PageRouteMetadata;
use Atom\Router\Route;
use Atom\Router\RouteMatcher;
use Atom\Security\CsrfTokenManagerInterface;
use Atom\Session\ArraySession;
use Atom\Session\SessionInterface;
use Atom\View\Component\ComponentRegistry;
use DateInterval;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;

final class AccountsModuleTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Route::clearRouter();
    }

    public function testModuleRendersLoginFormRegistersResourcesAndLogoutComponent(): void
    {
        $app = new AccountsTestApplication();
        $form = $app->handle(new Request("GET", "/account/login", queryParams: [
            "return" => "/settings?tab=profile",
            "login" => "edin@example.com",
        ]));
        $stylesheet = $app->handle(new Request("GET", "/account/resources/accounts.css"));
        $components = $app->getInjector()->get(ComponentRegistry::class);
        $loginRoute = (new RouteMatcher($app->getRouter()))->match("GET", "/account/login");

        $this->assertSame(200, $form->getStatus());
        $this->assertSame("text/html; charset=utf-8", $form->headers()->get("Content-Type"));
        $this->assertSame("no-store", $form->headers()->get("Cache-Control"));
        $this->assertStringContainsString('<form method="post" action="/account/login"', $form->getContent());
        $this->assertStringContainsString('name="_action" value="login"', $form->getContent());
        $this->assertStringContainsString('name="return" value="/settings?tab=profile"', $form->getContent());
        $this->assertStringContainsString('value="edin@example.com"', $form->getContent());
        $this->assertMatchesRegularExpression('/name="_token"\s+value="[a-f0-9]{64}"/', $form->getContent());
        $this->assertSame(200, $stylesheet->getStatus());
        $this->assertSame("text/css; charset=utf-8", $stylesheet->headers()->get("Content-Type"));
        $this->assertStringContainsString(".accounts-card", $stylesheet->getContent());
        $this->assertSame(LogoutForm::class, $components->get("Accounts.LogoutForm"));
        $this->assertSame(
            LoginPage::class,
            $loginRoute->matchedRoute->getRouteEntry()->getMetadataOfType(PageRouteMetadata::class)?->pageClass
        );
    }

    public function testLoginRejectsInvalidCredentialsAndAcceptsValidCredentials(): void
    {
        $app = new AccountsTestApplication();
        $token = $this->token($app);

        $invalid = $app->handle(new Request("POST", "/account/login", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "login",
            "login" => "edin@example.com",
            "password" => "wrong",
            "return" => "https://attacker.example/steal",
        ], serverParams: ["REMOTE_ADDR" => "127.0.0.1"]));

        $this->assertSame(200, $invalid->getStatus());
        $this->assertStringContainsString("The provided credentials are invalid.", $invalid->getContent());
        $this->assertStringContainsString('name="return" value="/dashboard"', $invalid->getContent());
        $this->assertStringNotContainsString("wrong", $invalid->getContent());

        $valid = $app->handle(new Request("POST", "/account/login", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "login",
            "login" => "edin@example.com",
            "password" => "secret",
            "return" => "/settings?tab=profile",
        ], serverParams: ["REMOTE_ADDR" => "127.0.0.1"]));

        $this->assertSame(302, $valid->getStatus());
        $this->assertSame("/settings?tab=profile", $valid->headers()->get("Location"));
        $this->assertSame(42, $app->session->get("_atom_identity"));
    }

    public function testAuthenticatedUsersLeaveLoginAndCanLogout(): void
    {
        $app = new AccountsTestApplication();
        $token = $this->token($app);
        $app->session->put("_atom_identity", 42);

        $login = $app->handle(new Request("GET", "/account/login"));
        $logout = $app->handle(new Request("POST", "/account/logout", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
        ]));

        $this->assertSame(302, $login->getStatus());
        $this->assertSame("/dashboard", $login->headers()->get("Location"));
        $this->assertSame(302, $logout->getStatus());
        $this->assertSame("/goodbye", $logout->headers()->get("Location"));
        $this->assertFalse($app->session->has("_atom_identity"));
    }

    public function testLoginAndLogoutRequireValidCsrfAndLogoutRequiresIdentity(): void
    {
        $app = new AccountsTestApplication();

        $login = $app->handle(new Request("POST", "/account/login", parsedBody: [
            "login" => "edin@example.com",
            "password" => "secret",
        ]));
        $token = $this->token($app);
        $logout = $app->handle(new Request("POST", "/account/logout", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
        ]));

        $this->assertSame(403, $login->getStatus());
        $this->assertSame("Invalid CSRF token.", $login->getContent());
        $this->assertSame(401, $logout->getStatus());
        $this->assertSame("Authentication required.", $logout->getContent());
    }

    public function testLoginAttemptsAreRateLimitedByClientAndLogin(): void
    {
        $app = new AccountsTestApplication();
        $token = $this->token($app);
        $request = fn(): Request => new Request("POST", "/account/login", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "login",
            "login" => "edin@example.com",
            "password" => "wrong",
        ], serverParams: ["REMOTE_ADDR" => "192.0.2.10"]);

        $first = $app->handle($request());
        $second = $app->handle($request());
        $limited = $app->handle($request());

        $this->assertSame(200, $first->getStatus());
        $this->assertSame(200, $second->getStatus());
        $this->assertSame(429, $limited->getStatus());
        $this->assertSame("Too many requests.", $limited->getContent());
        $this->assertSame("2", $limited->headers()->get("X-RateLimit-Limit"));
        $this->assertNotNull($limited->headers()->get("Retry-After"));
    }

    public function testRedirectsAcceptOnlyLocalTargets(): void
    {
        $redirects = new AccountsRedirects();

        $this->assertSame("/account?tab=profile", $redirects->local("/account?tab=profile"));
        $this->assertSame("/fallback", $redirects->local("https://attacker.example", "/fallback"));
        $this->assertSame("/fallback", $redirects->local("//attacker.example", "/fallback"));
        $this->assertSame("/fallback", $redirects->local("/\\attacker.example", "/fallback"));
        $this->assertSame("/fallback", $redirects->local("/%0d%0aLocation:evil", "/fallback"));
        $this->assertSame("/", $redirects->local(null, "https://invalid.example"));
    }

    private function token(AccountsTestApplication $app): string
    {
        $response = $app->handle(new Request("GET", "/account/login"));
        preg_match('/name="_token"\s+value="([a-f0-9]{64})"/', $response->getContent(), $matches);

        return $matches[1] ?? "";
    }
}

final class AccountsTestApplication extends Application
{
    public readonly ArraySession $session;
    public readonly AccountsTestIdentityProvider $identities;
    public readonly AccountsTestCache $cache;

    public function __construct()
    {
        $this->session = new ArraySession(id: "accounts-test-session");
        $this->identities = new AccountsTestIdentityProvider();
        $this->cache = new AccountsTestCache();
        parent::__construct();
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(new AccountsTestServices($this->session, $this->identities, $this->cache));
    }

    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(Accounts::module(new AccountsOptions(
            title: "Welcome back",
            afterLogin: "/dashboard",
            afterLogout: "/goodbye",
            loginMaxAttempts: 2,
            loginWindowSeconds: 60
        )), "/account");
    }
}

final readonly class AccountsTestServices implements ServiceProviderInterface
{
    public function __construct(
        private ArraySession $session,
        private AccountsTestIdentityProvider $identities,
        private AccountsTestCache $cache
    ) {
    }

    public function register(Bindings $bindings): void
    {
        $bindings->value(SessionInterface::class, $this->session);
        $bindings->value(IdentityProviderInterface::class, $this->identities);
        $bindings->value(CacheInterface::class, $this->cache);
    }
}

final readonly class AccountsTestIdentity implements IdentityInterface
{
    public function identifier(): int
    {
        return 42;
    }
}

final readonly class AccountsTestIdentityProvider implements IdentityProviderInterface
{
    public function findByIdentifier(string|int $identifier): ?IdentityInterface
    {
        return $identifier === 42 ? new AccountsTestIdentity() : null;
    }

    public function findByLogin(string $login): ?IdentityInterface
    {
        return strtolower($login) === "edin@example.com" ? new AccountsTestIdentity() : null;
    }

    public function validateCredentials(
        IdentityInterface $identity,
        #[SensitiveParameter] string $password
    ): bool {
        return $identity instanceof AccountsTestIdentity && $password === "secret";
    }
}

final class AccountsTestCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): void
    {
        $this->items[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function remember(string $key, DateInterval|int|null $ttl, callable $factory): mixed
    {
        return $this->items[$key] ??= $factory();
    }

    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }
        $this->set($key, $value, $ttl);
        return true;
    }

    public function increment(string $key, int $amount = 1, DateInterval|int|null $ttl = null): int
    {
        $value = (int) ($this->items[$key] ?? 0) + $amount;
        $this->items[$key] = $value;
        return $value;
    }
}
