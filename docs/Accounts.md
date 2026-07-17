# Accounts Module

[Atom Framework](Index.md)

The optional Accounts module provides a basic login page and logout endpoint on top of Atom's [identity and authentication](Identity.md) foundation. The login workflow uses Atom's page foundation: `LoginPage::get()` prepares the initial page and a `#[PageAction]` handles submission. The module does not define a user model, database schema, registration, or password-reset workflow.

## Requirements

The application must bind `IdentityProviderInterface` to its user adapter before using the module:

```php
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use Atom\Identity\IdentityProviderInterface;

final readonly class ApplicationIdentityServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(IdentityProviderInterface::class)
            ->to(UserIdentityProvider::class);
    }
}
```

Add that provider from the application's `services()` hook. See [Identity and Authentication](Identity.md) for the complete provider contract.

## Registration

Mount the module from `Application::modules()`:

```php
use Atom\Module\ModuleRegistry;
use Atom\Modules\Accounts\Accounts;

protected function modules(ModuleRegistry $modules): void
{
    $modules->add(Accounts::module(), "/account");
}
```

This registers:

```text
GET   /account/login       Login form
POST  /account/login       Credential authentication
POST  /account/logout      Session logout
GET   /account/resources/* Module styles
```

The POST routes use CSRF protection. Login attempts are rate limited, and logout requires an authenticated identity. Successful login and logout regenerate the session ID through the core authenticator.

## Configuration

Pass explicit options when registering the module:

```php
use Atom\Modules\Accounts\AccountsOptions;

$modules->add(Accounts::module(new AccountsOptions(
    title: "Sign in to Admin",
    afterLogin: "/admin",
    afterLogout: "/",
    loginMaxAttempts: 5,
    loginWindowSeconds: 60
)), "/account");
```

Or use environment settings with `Accounts::module()`:

```env
ACCOUNTS_TITLE="Sign in"
ACCOUNTS_AFTER_LOGIN=/dashboard
ACCOUNTS_AFTER_LOGOUT=/
ACCOUNTS_LOGIN_MAX_ATTEMPTS=5
ACCOUNTS_LOGIN_WINDOW_SECONDS=60
```

Set `ACCOUNTS_LOGIN_MAX_ATTEMPTS=0` to disable login throttling. Return destinations must be local absolute paths. External, protocol-relative, backslash-containing, and control-character redirects are rejected.

## Returning After Login

Pass a local `return` query parameter to the login form:

```text
/account/login?return=/settings/profile
```

The form carries that destination through authentication. Invalid destinations fall back to `afterLogin`.

## Logout Form

The module registers an `Accounts.LogoutForm` component that includes the required CSRF token:

```html
<Accounts.LogoutForm />
```

Customize its label and class when needed:

```html
<Accounts.LogoutForm label="Log out" class="account-menu__logout" />
```

Logout is intentionally POST-only. Do not replace it with a logout link that performs a state-changing GET request.

## Custom Login Template

The built-in form is the `LoginPage.atom.html` template adjacent to the module page. To replace it, provide an absolute `.atom.html` template path:

```php
$modules->add(Accounts::module(new AccountsOptions(
    loginTemplate: __DIR__ . "/../Views/account-login.atom.html"
)), "/account");
```

The template receives the normal page context through `$this`. The login page exposes its title, form action, stylesheet URL, CSRF token, validated return destination, retained login value, and generic authentication error. Password values are read directly from the request and are never stored in page state or passed back to the template.

## Scope

This first slice intentionally excludes registration, remember-me cookies, password reset, email verification, and database migrations. A later `accounts:publish` command can provide an optional application-owned model, provider, and migrations without coupling this module to one schema.
