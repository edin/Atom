<?php

declare(strict_types=1);

namespace Atom\Tests\Identity;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Identity\AuthenticateMiddleware;
use Atom\Identity\AuthenticatorInterface;
use Atom\Identity\GuestMiddleware;
use Atom\Identity\IdentityInterface;
use Atom\Identity\IdentityProviderInterface;
use Atom\Identity\IdentityServices;
use Atom\Identity\NativePasswordHasher;
use Atom\Identity\PasswordHasherInterface;
use Atom\Identity\SessionAuthenticator;
use Atom\Session\ArraySession;
use Atom\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;

final class IdentityTest extends TestCase
{
    public function testNativePasswordHasherHashesVerifiesAndDetectsRehashes(): void
    {
        $hasher = new NativePasswordHasher(PASSWORD_BCRYPT, ["cost" => 4]);
        $hash = $hasher->hash("correct horse battery staple");

        $this->assertNotSame("correct horse battery staple", $hash);
        $this->assertTrue($hasher->verify("correct horse battery staple", $hash));
        $this->assertFalse($hasher->verify("wrong password", $hash));
        $this->assertFalse($hasher->needsRehash($hash));
        $this->assertTrue((new NativePasswordHasher(PASSWORD_BCRYPT, ["cost" => 5]))->needsRehash($hash));
    }

    public function testAuthenticatorAttemptsLoginRestoresIdentityAndLogsOut(): void
    {
        $identity = new TestIdentity(42, "edin@example.com");
        $provider = new TestIdentityProvider([$identity], "secret");
        $session = new ArraySession(["cart" => [10]], "guest-session");
        $authenticator = new SessionAuthenticator($provider, $session);

        $this->assertFalse($authenticator->attempt("edin@example.com", "wrong"));
        $this->assertTrue($authenticator->guest());
        $this->assertSame("guest-session", $session->id());

        $this->assertTrue($authenticator->attempt("edin@example.com", "secret"));
        $authenticatedSession = $session->id();
        $this->assertNotSame("guest-session", $authenticatedSession);
        $this->assertTrue($authenticator->check());
        $this->assertSame($identity, $authenticator->identity());
        $this->assertSame(42, $session->get(SessionAuthenticator::SESSION_KEY));

        $restored = new SessionAuthenticator($provider, $session);
        $this->assertSame($identity, $restored->identity());
        $this->assertSame(1, $provider->identifierLookups);

        $restored->logout();
        $this->assertTrue($restored->guest());
        $this->assertFalse($session->has(SessionAuthenticator::SESSION_KEY));
        $this->assertNotSame($authenticatedSession, $session->id());
        $this->assertSame([10], $session->get("cart"));
    }

    public function testAuthenticatorClearsInvalidAndMissingStoredIdentities(): void
    {
        $provider = new TestIdentityProvider([], "secret");
        $invalidSession = new ArraySession([
            SessionAuthenticator::SESSION_KEY => ["invalid"],
        ]);
        $invalid = new SessionAuthenticator($provider, $invalidSession);

        $this->assertNull($invalid->identity());
        $this->assertFalse($invalidSession->has(SessionAuthenticator::SESSION_KEY));

        $missingSession = new ArraySession([
            SessionAuthenticator::SESSION_KEY => "missing-user",
        ]);
        $missing = new SessionAuthenticator($provider, $missingSession);

        $this->assertNull($missing->identity());
        $this->assertFalse($missingSession->has(SessionAuthenticator::SESSION_KEY));
    }

    public function testRefreshReloadsTheCurrentIdentityFromTheProvider(): void
    {
        $first = new TestIdentity("user-1", "first@example.com");
        $second = new TestIdentity("user-1", "updated@example.com");
        $provider = new TestIdentityProvider([$first], "secret");
        $session = new ArraySession([SessionAuthenticator::SESSION_KEY => "user-1"]);
        $authenticator = new SessionAuthenticator($provider, $session);

        $this->assertSame($first, $authenticator->identity());
        $provider->identities = [$second];
        $this->assertSame($first, $authenticator->identity());
        $this->assertSame($second, $authenticator->refresh());
        $this->assertSame(2, $provider->identifierLookups);
    }

    public function testAuthenticationMiddlewaresProtectAuthenticatedAndGuestRoutes(): void
    {
        $identity = new TestIdentity(7, "user@example.com");
        $provider = new TestIdentityProvider([$identity], "secret");
        $session = new ArraySession();
        $authenticator = new SessionAuthenticator($provider, $session);
        $authenticated = new AuthenticateMiddleware($authenticator);
        $guest = new GuestMiddleware($authenticator);
        $handler = new IdentityTestHandler();
        $request = new Request("GET", "/account");

        $unauthenticated = $authenticated->process($request, $handler);
        $this->assertSame(401, $unauthenticated->getStatus());
        $this->assertSame("Authentication required.", $unauthenticated->getContent());
        $this->assertSame("no-store", $unauthenticated->headers()->get("Cache-Control"));

        $guestResponse = $guest->process($request, $handler);
        $this->assertSame("accepted", $guestResponse->getContent());

        $authenticator->login($identity);
        $authenticatedResponse = $authenticated->process($request, $handler);
        $this->assertSame("accepted", $authenticatedResponse->getContent());

        $forbidden = $guest->process($request, $handler);
        $this->assertSame(403, $forbidden->getStatus());
        $this->assertSame("Guest access required.", $forbidden->getContent());
        $this->assertSame(2, $handler->calls);
    }

    public function testIdentityServicesRegisterScopedAuthenticationDependencies(): void
    {
        $bindings = Bindings::create();
        $bindings->value(SessionInterface::class, new ArraySession());
        $bindings->value(IdentityProviderInterface::class, new TestIdentityProvider([], "secret"));
        (new IdentityServices())->register($bindings);
        $injector = new Injector($bindings);
        $firstContext = new InjectionContext();
        $secondContext = new InjectionContext();

        $authenticator = $injector->get(AuthenticatorInterface::class, $firstContext);

        $this->assertInstanceOf(SessionAuthenticator::class, $authenticator);
        $this->assertSame($authenticator, $injector->get(AuthenticatorInterface::class, $firstContext));
        $this->assertNotSame($authenticator, $injector->get(AuthenticatorInterface::class, $secondContext));
        $this->assertInstanceOf(NativePasswordHasher::class, $injector->get(PasswordHasherInterface::class));
        $this->assertSame(
            $injector->get(PasswordHasherInterface::class),
            $injector->get(PasswordHasherInterface::class)
        );
        $this->assertInstanceOf(AuthenticateMiddleware::class, $injector->get(
            AuthenticateMiddleware::class,
            $firstContext
        ));
        $this->assertInstanceOf(GuestMiddleware::class, $injector->get(GuestMiddleware::class, $firstContext));
    }
}

final readonly class TestIdentity implements IdentityInterface
{
    public function __construct(private string|int $id, public string $login)
    {
    }

    public function identifier(): string|int
    {
        return $this->id;
    }
}

final class TestIdentityProvider implements IdentityProviderInterface
{
    /** @var TestIdentity[] */
    public array $identities;
    public int $identifierLookups = 0;

    /**
     * @param TestIdentity[] $identities
     */
    public function __construct(array $identities, private readonly string $password)
    {
        $this->identities = $identities;
    }

    public function findByIdentifier(string|int $identifier): ?IdentityInterface
    {
        $this->identifierLookups++;
        foreach ($this->identities as $identity) {
            if ($identity->identifier() === $identifier) {
                return $identity;
            }
        }
        return null;
    }

    public function findByLogin(string $login): ?IdentityInterface
    {
        foreach ($this->identities as $identity) {
            if ($identity->login === $login) {
                return $identity;
            }
        }
        return null;
    }

    public function validateCredentials(
        IdentityInterface $identity,
        #[SensitiveParameter] string $password
    ): bool {
        return $password === $this->password;
    }
}

final class IdentityTestHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public function handle(Request $request): Response
    {
        $this->calls++;
        return (new Response())->content("accepted");
    }
}
