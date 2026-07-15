# Sessions

[Atom Framework](Index.md)

Sessions are core request infrastructure under `Atom\Session`. They are not a feature module and do not need to be registered by applications.

Atom binds `SessionInterface` to the native PHP session store by default. The store is request-scoped and lazy: constructing or injecting it does not start a PHP session. The session starts on the first read or write and is saved and closed automatically when `Application::handle()` finishes.

## Using Sessions

Inject `SessionInterface` into a page, controller, route action, or application service:

```php
use Atom\Session\SessionInterface;

final class AccountService
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function rememberUser(int $userId): void
    {
        $this->session->put("user_id", $userId);
    }
}
```

The core operations are:

```php
$session->put("user_id", 42);
$session->get("user_id");
$session->get("missing", "default");
$session->has("user_id");
$session->pull("one_time_value");
$session->remove("user_id");
$session->all();
$session->clear();
```

Stored `null` values are distinct from missing keys when using `has()`.

Regenerate the session ID after authentication or another privilege change:

```php
$session->regenerate(deleteOldSession: true);
```

Invalidate the complete session during logout:

```php
$session->invalidate();
```

The current application also exposes `getSession()` for infrastructure code that already has the `Application` instance. Constructor injection is preferred for ordinary services.

## Reading Request Cookies

`Request::cookies()` exposes the parsed, read-only `CookieCollection`:

```php
$theme = $request->cookies()->string("theme", "system");

if ($request->cookies()->has("consent")) {
    // ...
}
```

Cookie names are preserved exactly, including names containing dots. Values use percent decoding without converting literal `+` characters into spaces. Raw access remains available through `$request->headers()->get("Cookie")` when needed.

## Flash Data

`FlashBag` stores values for the next request:

```php
use Atom\Session\FlashBag;

$flash->put("notice", "Article saved.");
$response->redirect("/articles");
```

On the following session request:

```php
$message = $flash->pull("notice");
```

Available flash operations are `put()`, `now()`, `get()`, `has()`, `pull()`, `all()`, `keep()`, and `reflash()`.

`Page::flash()` remains a current-page presentation helper used by `Toast` and `SnackBar`. `FlashBag` is the request-to-request storage primitive; a later UI integration can map stored flash payloads into those components without coupling the session layer to the Framework module.

## Configuration

Native sessions use these environment options:

```env
SESSION_NAME=ATOMSESSID
SESSION_LIFETIME=0
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Lax
SESSION_STRICT_MODE=true
```

When `SESSION_SECURE` is omitted, Atom enables secure cookies automatically for HTTPS requests. `SESSION_SAME_SITE` accepts `Lax`, `Strict`, or `None`.

## Tests

Use `ArraySession` when application tests should not touch PHP's global session state:

```php
$session = new ArraySession(["user_id" => 42], id: "test-session");
```

Applications can replace the `SessionInterface` binding from an application service provider. Keep the replacement request-scoped so it follows the normal request lifecycle.
