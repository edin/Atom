# CSRF Protection

[Atom Framework](Index.md)

Atom provides session-backed cross-site request forgery protection under `Atom\Security`. It is core request infrastructure, not a feature module.

CSRF protection is opt-in per route or route group so bearer-token APIs and other stateless routes do not start sessions.

## Protect Routes

Attach `CsrfMiddleware` to a browser route:

```php
use Atom\Security\CsrfMiddleware;
use Atom\Router\Route;

Route::post("/articles", fn() => "saved")
    ->middleware(CsrfMiddleware::class);
```

Or protect a browser-facing group:

```php
Route::group("/admin", function () {
    Route::post("/articles", fn() => "saved");
    Route::delete("/articles/{id}", fn(int $id) => "deleted");
})->middleware(CsrfMiddleware::class);
```

Auto-discovered Pages can declare the middleware on `PageRoute`; it applies to both rendering and action routes:

```php
#[PageRoute("/articles", middleware: CsrfMiddleware::class)]
final class ArticlesPage extends Page
{
    #[PageAction("save")]
    public function save(): void
    {
    }
}
```

`GET`, `HEAD`, and `OPTIONS` requests pass through without reading the session. Other methods must provide a valid token through the `_token` request field or `X-CSRF-Token` header.

Invalid requests receive a `403 Forbidden` plain-text response with `Cache-Control: no-store` and do not reach the route handler.

## Component Forms

Enable token rendering on a Components module form:

```html
<Form submit="save" csrf>
    <TextField label="Title" name="title" />
    <Button type="submit">Save</Button>
</Form>
```

The form prepends:

```html
<input type="hidden" name="_token" value="...">
```

GET forms do not render a token or start a session.

## Atom Actions

Atom's browser runtime automatically sends `X-CSRF-Token` on unsafe enhanced requests when the document contains either:

```html
<input type="hidden" name="_token" value="...">
```

or:

```html
<meta name="csrf-token" content="...">
```

A CSRF-enabled component form provides the hidden input automatically. For standalone `atom:action` controls outside a form, render the meta token from an injected `CsrfTokenManagerInterface` in the application layout.

## Token Manager

Application services can inject `CsrfTokenManagerInterface` directly:

```php
use Atom\Security\CsrfTokenManagerInterface;

final class SecurityContext
{
    public function __construct(private CsrfTokenManagerInterface $csrf)
    {
    }

    public function token(): string
    {
        return $this->csrf->token();
    }
}
```

Available operations are:

```php
$csrf->token();      // Return the current token, creating it if necessary.
$csrf->validate($candidate);
$csrf->refresh();    // Rotate the token.
$csrf->clear();
```

Tokens contain 32 random bytes encoded as 64 hexadecimal characters and comparisons use `hash_equals()`. Rotate the token after authentication or another privilege change. Invalid requests without an existing token do not create a token automatically.
