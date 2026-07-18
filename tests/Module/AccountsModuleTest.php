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
use Atom\Modules\Accounts\AccountManagerInterface;
use Atom\Modules\Accounts\Accounts;
use Atom\Modules\Accounts\AccountsOptions;
use Atom\Modules\Accounts\AccountsRedirects;
use Atom\Modules\Accounts\Components\AccountsLayout;
use Atom\Modules\Accounts\Components\AccountsPanel;
use Atom\Modules\Accounts\Components\Button;
use Atom\Modules\Accounts\Components\Error;
use Atom\Modules\Accounts\Components\Field;
use Atom\Modules\Accounts\Components\LogoutForm;
use Atom\Modules\Accounts\Components\Message;
use Atom\Modules\Accounts\Middlewares\AccountsPageMiddleware;
use Atom\Modules\Accounts\Middlewares\LoginRateLimitMiddleware;
use Atom\Modules\Accounts\NullAccountManager;
use Atom\Modules\Accounts\Pages\ForgotPasswordPage;
use Atom\Modules\Accounts\Pages\LoginPage;
use Atom\Modules\Accounts\Pages\RegisterPage;
use Atom\Modules\Accounts\Pages\ResetPasswordPage;
use Atom\Modules\Accounts\RegisterAccount;
use Atom\Page\PageRouteMetadata;
use Atom\Router\Route;
use Atom\Router\RouteMatcher;
use Atom\Security\CsrfMiddleware;
use Atom\Security\CsrfTokenManagerInterface;
use Atom\Session\ArraySession;
use Atom\Session\SessionInterface;
use Atom\View\Component\AttributeBag;
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
        $this->assertStringContainsString('<title>Welcome back</title>', $form->getContent());
        $this->assertStringContainsString('Enter your credentials to continue.', $form->getContent());
        $this->assertStringContainsString('<main class="accounts-shell" atom:update-root>', $form->getContent());
        $this->assertStringContainsString('<section class="accounts-card" aria-labelledby="accounts-title">', $form->getContent());
        $this->assertStringContainsString('<label class="accounts-field" for="login">', $form->getContent());
        $this->assertStringContainsString('<button type="submit" class="accounts-submit">Sign in</button>', $form->getContent());
        $this->assertSame(200, $stylesheet->getStatus());
        $this->assertSame("text/css; charset=utf-8", $stylesheet->headers()->get("Content-Type"));
        $this->assertStringContainsString(".accounts-card", $stylesheet->getContent());
        $this->assertSame(AccountsLayout::class, $components->get("Accounts.Layout"));
        $this->assertSame(AccountsPanel::class, $components->get("Accounts.Panel"));
        $this->assertSame(Field::class, $components->get("Accounts.Field"));
        $this->assertSame(Button::class, $components->get("Accounts.Button"));
        $this->assertSame(Error::class, $components->get("Accounts.Error"));
        $this->assertSame(Message::class, $components->get("Accounts.Message"));
        $this->assertSame(LogoutForm::class, $components->get("Accounts.LogoutForm"));
        $this->assertInstanceOf(NullAccountManager::class, $app->getInjector()->get(AccountManagerInterface::class));
        $this->assertSame(
            LoginPage::class,
            $loginRoute->matchedRoute->getRouteEntry()->getMetadataOfType(PageRouteMetadata::class)?->pageClass
        );
        $this->assertSame("Sign in", $loginRoute->matchedRoute->getRouteEntry()->getTitle());
        $this->assertSame(
            "Display or submit the account login form.",
            $loginRoute->matchedRoute->getRouteEntry()->getDescription()
        );
        $this->assertSame([
            AccountsPageMiddleware::class,
            CsrfMiddleware::class,
            LoginRateLimitMiddleware::class,
        ], $loginRoute->matchedRoute->getRouteEntry()->getOwnMiddlewares());
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

    public function testModuleRendersRegistrationAndPasswordRecoveryPages(): void
    {
        $app = new AccountsTestApplication();

        $register = $app->handle(new Request("GET", "/account/register", queryParams: [
            "login" => "edin@example.com",
        ]));
        $forgot = $app->handle(new Request("GET", "/account/forgot-password"));
        $reset = $app->handle(new Request("GET", "/account/reset-password", queryParams: [
            "login" => "edin@example.com",
            "token" => "reset-token",
        ]));

        $this->assertSame(200, $register->getStatus());
        $this->assertStringContainsString('<form method="post" action="/account/register"', $register->getContent());
        $this->assertStringContainsString('value="edin@example.com"', $register->getContent());
        $this->assertStringContainsString('name="password_confirmation"', $register->getContent());
        $this->assertSame(200, $forgot->getStatus());
        $this->assertStringContainsString('<form method="post" action="/account/forgot-password"', $forgot->getContent());
        $this->assertStringContainsString('name="_action" value="sendResetLink"', $forgot->getContent());
        $this->assertSame(200, $reset->getStatus());
        $this->assertStringContainsString('<form method="post" action="/account/reset-password"', $reset->getContent());
        $this->assertStringContainsString('name="token" value="reset-token"', $reset->getContent());
        $this->assertStringNotContainsString("invalid or incomplete", $reset->getContent());

        $matcher = new RouteMatcher($app->getRouter());
        $this->assertSame(
            RegisterPage::class,
            $matcher->match("GET", "/account/register")->matchedRoute->getRouteEntry()
                ->getMetadataOfType(PageRouteMetadata::class)?->pageClass
        );
        $this->assertSame(
            ForgotPasswordPage::class,
            $matcher->match("GET", "/account/forgot-password")->matchedRoute->getRouteEntry()
                ->getMetadataOfType(PageRouteMetadata::class)?->pageClass
        );
        $this->assertSame(
            ResetPasswordPage::class,
            $matcher->match("GET", "/account/reset-password")->matchedRoute->getRouteEntry()
                ->getMetadataOfType(PageRouteMetadata::class)?->pageClass
        );
    }

    public function testAccountWorkflowStubsReturnSafeMessages(): void
    {
        $app = new AccountsTestApplication();
        $token = $this->token($app);

        $register = $app->handle(new Request("POST", "/account/register", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "register",
            "login" => "new@example.com",
            "password" => "secret",
            "password_confirmation" => "secret",
        ]));
        $forgot = $app->handle(new Request("POST", "/account/forgot-password", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "sendResetLink",
            "login" => "unknown@example.com",
        ]));
        $reset = $app->handle(new Request("POST", "/account/reset-password", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "resetPassword",
            "login" => "edin@example.com",
            "token" => "reset-token",
            "password" => "new-secret",
            "password_confirmation" => "new-secret",
        ]));

        $this->assertSame(200, $register->getStatus());
        $this->assertStringContainsString("Account registration is not configured.", $register->getContent());
        $this->assertStringNotContainsString('value="secret"', $register->getContent());
        $this->assertSame(200, $forgot->getStatus());
        $this->assertStringContainsString(
            "If an account matches that address, a password reset link will be sent.",
            $forgot->getContent()
        );
        $this->assertStringNotContainsString("No account", $forgot->getContent());
        $this->assertSame(200, $reset->getStatus());
        $this->assertStringContainsString("Password reset is not configured.", $reset->getContent());
        $this->assertStringNotContainsString("new-secret", $reset->getContent());
    }

    public function testNewAccountPageActionsRequireValidCsrf(): void
    {
        $app = new AccountsTestApplication();

        foreach (["register", "forgot-password", "reset-password"] as $path) {
            $response = $app->handle(new Request("POST", "/account/{$path}", parsedBody: [
                "_action" => "invalid",
            ]));

            $this->assertSame(403, $response->getStatus());
            $this->assertSame("Invalid CSRF token.", $response->getContent());
        }
    }

    public function testCustomAccountManagerReceivesAdditionalRegistrationFieldsAndHandlesWorkflows(): void
    {
        $accounts = new RecordingAccountManager();
        $app = new AccountsTestApplication($accounts);
        $token = $this->token($app);

        $register = $app->handle(new Request("POST", "/account/register", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "register",
            "_state" => "ignored-transport-state",
            "login" => "new@example.com",
            "password" => "secret",
            "password_confirmation" => "secret",
            "name" => "Edin",
            "organization_id" => "7",
            "newsletter" => "true",
        ]));

        $this->assertSame(302, $register->getStatus());
        $this->assertSame("/dashboard", $register->headers()->get("Location"));
        $this->assertSame($accounts, $app->getInjector()->get(AccountManagerInterface::class));
        $this->assertNotNull($accounts->registration);
        $this->assertSame("new@example.com", $accounts->registration->login);
        $this->assertSame("secret", $accounts->registration->password());
        $this->assertSame("Edin", $accounts->registration->string("name"));
        $this->assertSame(7, $accounts->registration->int("organization_id"));
        $this->assertTrue($accounts->registration->bool("newsletter"));
        $this->assertSame([
            "name" => "Edin",
            "organization_id" => "7",
            "newsletter" => "true",
        ], $accounts->registration->fields());
        $this->assertSame(42, $app->session->get("_atom_identity"));

        $forgot = $app->handle(new Request("POST", "/account/forgot-password", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "sendResetLink",
            "login" => "new@example.com",
        ]));
        $reset = $app->handle(new Request("POST", "/account/reset-password", parsedBody: [
            CsrfTokenManagerInterface::FIELD_NAME => $token,
            "_action" => "resetPassword",
            "login" => "new@example.com",
            "token" => "valid-token",
            "password" => "new-secret",
            "password_confirmation" => "new-secret",
        ]));

        $this->assertSame(200, $forgot->getStatus());
        $this->assertSame(["new@example.com"], $accounts->resetRequests);
        $this->assertSame(302, $reset->getStatus());
        $this->assertSame("/account/login?login=new%40example.com", $reset->headers()->get("Location"));
        $this->assertSame(["new@example.com", "valid-token", "new-secret"], $accounts->passwordReset);
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

    public function testRegistrationAndPasswordRecoveryOperationsUseIndependentRateLimits(): void
    {
        $app = new AccountsTestApplication();
        $token = $this->token($app);
        $request = static fn(string $path, array $body): Request => new Request(
            "POST",
            "/account/{$path}",
            parsedBody: [CsrfTokenManagerInterface::FIELD_NAME => $token, ...$body],
            serverParams: ["REMOTE_ADDR" => "192.0.2.20"]
        );

        $operations = [
            "register" => [
                "_action" => "register",
                "login" => "new@example.com",
                "password" => "secret",
                "password_confirmation" => "secret",
            ],
            "forgot-password" => [
                "_action" => "sendResetLink",
                "login" => "new@example.com",
            ],
            "reset-password" => [
                "_action" => "resetPassword",
                "login" => "new@example.com",
                "token" => "reset-token",
                "password" => "new-secret",
                "password_confirmation" => "new-secret",
            ],
        ];

        foreach ($operations as $path => $body) {
            $first = $app->handle($request($path, $body));
            $second = $app->handle($request($path, $body));
            $limited = $app->handle($request($path, $body));

            $this->assertSame(200, $first->getStatus());
            $this->assertSame(200, $second->getStatus());
            $this->assertSame(429, $limited->getStatus());
            $this->assertSame("2", $limited->headers()->get("X-RateLimit-Limit"));
            $this->assertNotNull($limited->headers()->get("Retry-After"));
        }
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

    public function testAccountFieldForwardsEnhancementAttributesToStableNativeInput(): void
    {
        $field = new Field();
        $field->label = "Email";
        $field->name = "email";
        $field->attributes = new AttributeBag([
            "atom:change" => "validateEmail",
            "data-track" => "account-email",
        ]);

        $html = $field->render();

        $this->assertStringContainsString('<label class="accounts-field" for="email">', $html);
        $this->assertStringContainsString('id="email" name="email"', $html);
        $this->assertStringContainsString('atom:change="validateEmail"', $html);
        $this->assertStringContainsString('data-track="account-email"', $html);
    }

    public function testAccountErrorRendersOnlyWhenItHasAMessage(): void
    {
        $error = new Error();
        $error->attributes = new AttributeBag();

        $this->assertSame("", $error->render());

        $error->message = "Invalid <credentials>.";

        $this->assertSame(
            '<div class="accounts-error" role="alert">Invalid &lt;credentials&gt;.</div>',
            $error->render()
        );
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

    public function __construct(private readonly ?AccountManagerInterface $accounts = null)
    {
        $this->session = new ArraySession(id: "accounts-test-session");
        $this->identities = new AccountsTestIdentityProvider();
        $this->cache = new AccountsTestCache();
        parent::__construct();
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(new AccountsTestServices($this->session, $this->identities, $this->cache, $this->accounts));
    }

    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(Accounts::module(new AccountsOptions(
            title: "Welcome back",
            afterLogin: "/dashboard",
            afterLogout: "/goodbye",
            loginMaxAttempts: 2,
            loginWindowSeconds: 60,
            registerMaxAttempts: 2,
            registerWindowSeconds: 60,
            forgotPasswordMaxAttempts: 2,
            forgotPasswordWindowSeconds: 60,
            resetPasswordMaxAttempts: 2,
            resetPasswordWindowSeconds: 60
        )), "/account");
    }
}

final readonly class AccountsTestServices implements ServiceProviderInterface
{
    public function __construct(
        private ArraySession $session,
        private AccountsTestIdentityProvider $identities,
        private AccountsTestCache $cache,
        private ?AccountManagerInterface $accounts = null
    ) {
    }

    public function register(Bindings $bindings): void
    {
        $bindings->value(SessionInterface::class, $this->session);
        $bindings->value(IdentityProviderInterface::class, $this->identities);
        $bindings->value(CacheInterface::class, $this->cache);
        if ($this->accounts !== null) {
            $bindings->value(AccountManagerInterface::class, $this->accounts);
        }
    }
}

final class RecordingAccountManager implements AccountManagerInterface
{
    public ?RegisterAccount $registration = null;
    /** @var string[] */
    public array $resetRequests = [];
    /** @var string[] */
    public array $passwordReset = [];

    public function register(RegisterAccount $account): IdentityInterface
    {
        $this->registration = $account;

        return new AccountsTestIdentity();
    }

    public function requestPasswordReset(string $login): void
    {
        $this->resetRequests[] = $login;
    }

    public function resetPassword(string $login, string $token, string $password): bool
    {
        $this->passwordReset = [$login, $token, $password];

        return true;
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
