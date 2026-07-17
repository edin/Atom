# Identity and Authentication

[Atom Framework](Index.md)

Atom provides UI-independent identity and session-authentication primitives. Applications own their user model and persistence. Login, registration, and password-reset pages belong in an optional account module built on this foundation.

## Identity

An authenticatable application object implements `IdentityInterface`:

```php
use Atom\Identity\IdentityInterface;

final class User implements IdentityInterface
{
    public int $id;

    public function identifier(): string|int
    {
        return $this->id;
    }
}
```

Identifiers are the only identity value stored in the session. Complete user objects are loaded from the application provider for each request.

## Identity Provider

The application supplies an `IdentityProviderInterface` adapter:

```php
use Atom\Identity\IdentityInterface;
use Atom\Identity\IdentityProviderInterface;
use Atom\Identity\PasswordHasherInterface;
use SensitiveParameter;

final readonly class UserIdentityProvider implements IdentityProviderInterface
{
    public function __construct(private PasswordHasherInterface $passwords)
    {
    }

    public function findByIdentifier(string|int $identifier): ?IdentityInterface
    {
        return User::find($identifier);
    }

    public function findByLogin(string $login): ?IdentityInterface
    {
        return User::query()->where("email", $login)->first();
    }

    public function validateCredentials(
        IdentityInterface $identity,
        #[SensitiveParameter] string $password
    ): bool {
        return $identity instanceof User
            && $this->passwords->verify($password, $identity->passwordHash);
    }
}
```

Register the application adapter through a service provider:

```php
$bindings->bind(IdentityProviderInterface::class)
    ->to(UserIdentityProvider::class);
```

The framework does not register a default identity provider because it does not assume a user model, table, or login field.

## Authentication

Inject `AuthenticatorInterface` into application services, controllers, or pages:

```php
use Atom\Identity\AuthenticatorInterface;

final readonly class LoginAction
{
    public function __construct(private AuthenticatorInterface $auth)
    {
    }

    public function login(string $email, string $password): bool
    {
        return $this->auth->attempt($email, $password);
    }
}
```

The authenticator supports:

```php
$identity = $auth->identity();
$auth->check();
$auth->guest();
$auth->attempt($login, $password);
$auth->login($identity);
$auth->logout();
$auth->refresh();
```

Successful login and logout regenerate the session ID. Logout removes only the identity and preserves unrelated session data. An identity deleted from storage is automatically removed from the session the next time it is resolved.

`Application::getAuthenticator()` provides access from an initialized application when constructor injection is not practical.

## Password Hashing

`PasswordHasherInterface` is bound to `NativePasswordHasher`, which uses PHP's `password_hash`, `password_verify`, and `password_needs_rehash` functions with `PASSWORD_DEFAULT`.

```php
$hash = $passwords->hash($plainPassword);

if ($passwords->verify($plainPassword, $hash) && $passwords->needsRehash($hash)) {
    $hash = $passwords->hash($plainPassword);
}
```

Applications should store only password hashes and should never log or retain plain-text credentials.

## Route Middleware

Use `AuthenticateMiddleware` for routes that require a signed-in identity:

```php
use Atom\Identity\AuthenticateMiddleware;

Route::get("/account", $handler)
    ->middleware(AuthenticateMiddleware::class);
```

Unauthenticated requests receive a plain `401` response with `Cache-Control: no-store`.

Use `GuestMiddleware` for routes that must only be available before authentication:

```php
use Atom\Identity\GuestMiddleware;

Route::post("/login", $handler)
    ->middleware(GuestMiddleware::class);
```

Authenticated requests receive a plain `403` response. The future account UI module can provide redirect-oriented behavior without coupling the core authentication layer to HTML pages.
