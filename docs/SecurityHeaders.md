# Security Headers

[Atom Framework](Index.md)

`SecurityHeadersMiddleware` adds browser security headers after the route handler returns. It is stateless and does not start a session.

## Defaults

The default configuration adds:

```http
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
```

Content Security Policy and HTTP Strict Transport Security are disabled by default because they require deployment-specific decisions.

The middleware preserves a security header that the response has already set, allowing an endpoint to choose a stricter policy.

## Attach the Middleware

Use it on a route or route group:

```php
use Atom\Security\SecurityHeadersMiddleware;

Route::group("/", function () {
    // Browser routes
})->middleware(SecurityHeadersMiddleware::class);
```

Auto-discovered Pages can declare multiple middleware classes:

```php
#[PageRoute("/account", middleware: [
    SecurityHeadersMiddleware::class,
    CsrfMiddleware::class,
])]
final class AccountPage extends Page
{
}
```

## Configuration

All headers can be configured through environment options. An empty string disables a string-valued header:

```env
SECURITY_HEADERS_NO_SNIFF=true
SECURITY_HEADERS_FRAME_OPTIONS=SAMEORIGIN
SECURITY_HEADERS_REFERRER_POLICY=strict-origin-when-cross-origin
SECURITY_HEADERS_PERMISSIONS_POLICY="camera=(), microphone=(), geolocation=()"
SECURITY_HEADERS_CONTENT_SECURITY_POLICY="default-src 'self'"
SECURITY_HEADERS_CONTENT_SECURITY_POLICY_REPORT_ONLY=
SECURITY_HEADERS_HSTS_MAX_AGE=0
SECURITY_HEADERS_HSTS_INCLUDE_SUB_DOMAINS=true
SECURITY_HEADERS_HSTS_PRELOAD=false
```

HSTS is emitted only when `Request::isSecure()` detects HTTPS and `HSTS_MAX_AGE` is greater than zero. When TLS terminates at a reverse proxy, register `TrustedProxyMiddleware` before this middleware so only forwarded scheme information from configured proxies is accepted.

Only enable HSTS after confirming that the domain and, when selected, all subdomains are permanently available over HTTPS. CSP should be tested with the report-only option before enforcement on an existing application.
