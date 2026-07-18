# Accounts Module

[Atom Framework](Index.md)

The optional Accounts module provides login, registration, password-recovery, and logout pages on top of Atom's [identity and authentication](Identity.md) foundation. Each form uses Atom's page foundation: `get()` prepares the initial page and a `#[PageAction]` handles submission. The module does not define a user model, database schema, or reset-token storage.

## Requirements

The application must bind `IdentityProviderInterface` to its user adapter before using the module. To enable registration and password recovery, it can also bind `AccountManagerInterface`:

```php
use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use Atom\Identity\IdentityProviderInterface;
use Atom\Modules\Accounts\AccountManagerInterface;

final readonly class ApplicationIdentityServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(IdentityProviderInterface::class)
            ->to(UserIdentityProvider::class);
        $bindings->bind(AccountManagerInterface::class)
            ->to(ApplicationAccountManager::class);
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
GET   /account/register    Registration form
POST  /account/register    Registration action
GET   /account/forgot-password  Password reset request form
POST  /account/forgot-password  Password reset request action
GET   /account/reset-password   New-password form
POST  /account/reset-password   Password replacement action
POST  /account/logout      Session logout
GET   /account/resources/* Module styles
```

The POST routes use CSRF protection. Login, registration, reset-link requests, and password resets have independent rate limits. Logout requires an authenticated identity. Successful login and logout regenerate the session ID through the core authenticator.

Registration and password recovery delegate to one `AccountManagerInterface`. The module supplies `NullAccountManager` when the application does not bind one, preserving safe "not configured" behavior without requiring persistence or email services. The forgot-password response is identical whether an account exists or not.

The module provides `PasswordResetMail` with plain-text and HTML content. The application account
manager creates and stores the reset token, builds the absolute reset URL, and sends the provided
mailable through Atom's [mail service](Mail.md):

```php
public function requestPasswordReset(string $login): void
{
    $user = $this->users->findByEmail($login);
    if ($user === null) {
        return;
    }

    $token = $this->resetTokens->create($user);
    $url = $this->appUrl . "/account/reset-password?" . http_build_query([
        "login" => $user->email,
        "token" => $token,
    ]);

    $this->mailer->send(new PasswordResetMail($user->email, $url, $user->name));
}
```

Keeping token generation in the application prevents the module from assuming a database schema,
while mail composition and delivery use framework defaults.

For asynchronous delivery, dispatch a job containing only the recipient and reset URL. See
[Background Jobs and Queues](Queue.md) for a complete password-reset job example.

`AccountManagerInterface::register()` receives a `RegisterAccount` command. In addition to the canonical login and password, it contains every submitted registration field except `_token`, `_action`, `_state`, `password_confirmation`, and the canonical credential fields. Application managers must explicitly select the additional fields they recognize rather than mass-assigning `RegisterAccount::fields()`.

```php
public function register(RegisterAccount $account): ?IdentityInterface
{
    $user = new User();
    $user->email = $account->login;
    $user->name = $account->string("name");
    $user->password = $this->passwords->hash($account->password());

    $this->users->save($user);

    return $user;
}
```

## Configuration

Pass explicit options when registering the module:

```php
use Atom\Modules\Accounts\AccountsOptions;

$modules->add(Accounts::module(new AccountsOptions(
    title: "Sign in to Admin",
    afterLogin: "/admin",
    afterLogout: "/",
    loginMaxAttempts: 5,
    loginWindowSeconds: 60,
    registerMaxAttempts: 5,
    registerWindowSeconds: 60,
    forgotPasswordMaxAttempts: 3,
    forgotPasswordWindowSeconds: 60,
    resetPasswordMaxAttempts: 5,
    resetPasswordWindowSeconds: 60
)), "/account");
```

Or use environment settings with `Accounts::module()`:

```env
ACCOUNTS_TITLE="Sign in"
ACCOUNTS_AFTER_LOGIN=/dashboard
ACCOUNTS_AFTER_LOGOUT=/
ACCOUNTS_LOGIN_MAX_ATTEMPTS=5
ACCOUNTS_LOGIN_WINDOW_SECONDS=60
ACCOUNTS_REGISTER_MAX_ATTEMPTS=5
ACCOUNTS_REGISTER_WINDOW_SECONDS=60
ACCOUNTS_FORGOT_PASSWORD_MAX_ATTEMPTS=3
ACCOUNTS_FORGOT_PASSWORD_WINDOW_SECONDS=60
ACCOUNTS_RESET_PASSWORD_MAX_ATTEMPTS=5
ACCOUNTS_RESET_PASSWORD_WINDOW_SECONDS=60
```

Set an operation's maximum attempts to `0` to disable that throttle. Registration is keyed by client IP, reset-link requests by client IP and normalized login, and password resets by client IP and a hash of the submitted token. Return destinations must be local absolute paths. External, protocol-relative, backslash-containing, and control-character redirects are rejected.

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

The built-in forms are `.atom.html` templates adjacent to their module pages. To replace the login template, provide an absolute template path:

```php
$modules->add(Accounts::module(new AccountsOptions(
    loginTemplate: __DIR__ . "/../Views/account-login.atom.html"
)), "/account");
```

The equivalent options for the other pages are `registerTemplate`, `forgotPasswordTemplate`, and `resetPasswordTemplate`.

The template receives the normal page context through `$this`. It is a body template: the page declares `AccountsLayout` through its `layout` property, so the document shell and stylesheet are applied automatically. The module also registers `Accounts.Panel`, `Accounts.Field`, `Accounts.Button`, `Accounts.Error`, and `Accounts.Message` for composing a custom form without depending on the Framework UI module. Error and message components render nothing when their message is empty, so templates do not need a conditional around them.

The login page exposes its title, form action, stylesheet URL, CSRF token, validated return destination, retained login value, and generic authentication error. Password values are read directly from the request and are never stored in page state or passed back to the template.

## Scope

The framework intentionally leaves account persistence and reset-token generation and storage to the bound account manager. Remember-me cookies, email verification, and database migrations remain outside this slice. The framework [publisher](Publishing.md) now provides the collision-safe foundation for a later `accounts:publish` command that supplies an optional application-owned model, manager, provider, and fixed-name migrations without coupling this module to one schema.
