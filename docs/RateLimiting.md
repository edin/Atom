# Rate Limiting

`RateLimitMiddleware` uses `CacheInterface::increment()` to enforce atomic fixed-window request quotas. By default, each effective client IP and request path receives 60 attempts per 60 seconds.

## Configuration

```env
RATE_LIMIT_MAX_ATTEMPTS=60
RATE_LIMIT_WINDOW_SECONDS=60
RATE_LIMIT_KEY_PREFIX=http
RATE_LIMIT_INCLUDE_PATH=true
RATE_LIMIT_INCLUDE_METHOD=false
RATE_LIMIT_FAIL_OPEN=true
```

Set `RATE_LIMIT_MAX_ATTEMPTS=0` to disable the limiter. Fail-open mode allows requests when cache storage fails; fail-closed mode propagates the cache failure for applications where enforcement is more important than availability.

Allowed responses include `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `X-RateLimit-Reset`. Rejected requests return `429 Too Many Requests` with `Retry-After`.

## Route or group usage

Rate limiting can target sensitive routes without affecting the rest of the application:

```php
Route::post("/login", $handler)
    ->middleware(RateLimitMiddleware::class);
```

Attach it to a router group to share the configured policy while retaining separate path buckets.

## Global usage and order

```php
protected function middlewares(MiddlewareRegistry $middlewares): void
{
    $middlewares
        ->add(TrustedProxyMiddleware::class)
        ->add(RequestIdMiddleware::class)
        ->add(CorsMiddleware::class)
        ->add(RateLimitMiddleware::class);
}
```

Trusted proxies must run first so the limiter receives the real client IP. Put CORS before the limiter so preflight requests short-circuit without consuming quota.

## Custom identities

For authenticated-user or API-key limits, construct the middleware with a key resolver:

```php
$limiter = new RateLimitMiddleware(
    $cache,
    new RateLimitOptions(maxAttempts: 10, windowSeconds: 60),
    fn(Request $request): string => "user:" . $currentUser->id
);
```

File-backed counters are shared between PHP workers on one server. Multi-server deployments need a shared `CacheInterface` driver, such as a future database or Redis implementation, for globally consistent quotas.
