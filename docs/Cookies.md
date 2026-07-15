# Cookies

[Atom Framework](Index.md)

Atom provides separate APIs for incoming request cookies and outgoing response cookies.

## Reading Cookies

`Request::cookies()` returns a read-only `CookieCollection` parsed from the request's `Cookie` header:

```php
$theme = $request->cookies()->string("theme", "system");

if ($request->cookies()->has("consent")) {
    // ...
}
```

Cookie names are preserved exactly, including dots. Values use percent decoding without treating a literal `+` as a space. The raw header remains available through `$request->headers()->get("Cookie")`.

## Creating Cookies

`Cookie` is an immutable value object:

```php
use Atom\Http\Cookie;

$cookie = Cookie::create("theme", "dark")
    ->expiresAfter(3600)
    ->withPath("/")
    ->withSameSite("Lax")
    ->withHttpOnly()
    ->withSecure();
```

Cookie values are encoded safely when the `Set-Cookie` header is built. `SameSite=None` requires `Secure`.

## Setting Cookies on a Response

When code owns the response it returns, attach the cookie directly:

```php
return $response
    ->cookie($cookie)
    ->content("Preferences saved.");
```

Delete a cookie using the same path and domain with which it was created:

```php
return $response->removeCookie("theme", path: "/");
```

## Cookies from Services

Code that does not own the final response should inject the request-scoped `CookieJar`:

```php
use Atom\Http\CookieJar;

final class Preferences
{
    public function __construct(private CookieJar $cookies)
    {
    }

    public function useDarkTheme(): void
    {
        $this->cookies->set(Cookie::create("theme", "dark"));
    }
}
```

Atom applies the jar to the actual response returned by the dispatcher. The same behavior applies when a cookie is set on the request-scoped injected `Response` but the action returns a different response:

```php
Route::get("/preferences", function (Response $response): Response {
    $response->cookie(Cookie::create("theme", "dark"));

    return (new Response())->redirect("/account");
});
```

The cookie is transferred to the redirect response. A manually constructed response that is not returned and is not connected to the scoped jar cannot transfer its headers; inject `CookieJar` for that situation.

## Sessions

`NativeSession` uses the outgoing jar for the session cookie and disables PHP's automatic cookie and cache-limiter headers. This keeps `Application::handle()` from emitting headers and ensures the session cookie is attached to the final Atom response.
