# CORS

`CorsMiddleware` controls browser cross-origin requests and handles preflight `OPTIONS` requests before route matching when registered globally.

## Registration

```php
use Atom\Http\CorsMiddleware;
use Atom\Http\MiddlewareRegistry;

protected function middlewares(MiddlewareRegistry $middlewares): void
{
    $middlewares->add(CorsMiddleware::class);
}
```

Global middleware is appropriate for CORS because a preflight request must be answered even when the application has no explicit `OPTIONS` route.

## Configuration

```env
CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-CSRF-Token,X-Requested-With
CORS_EXPOSED_HEADERS=X-Request-Id
CORS_ALLOW_CREDENTIALS=false
CORS_MAX_AGE=600
```

No origins are allowed by default. Exact allowed origins are echoed in the response and add `Vary: Origin`. A wildcard origin is supported only when credentials are disabled.

Allowed preflights return `204 No Content`. Preflights using an unapproved origin, method, or header return `403 Forbidden`. Ordinary requests from disallowed origins continue without CORS response headers.
