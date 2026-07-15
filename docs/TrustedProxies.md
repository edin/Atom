# Trusted Proxies

`TrustedProxyMiddleware` safely applies proxy-provided client IP, scheme, and host information to the request. Forwarded headers are ignored unless the immediate network peer is explicitly trusted.

## Configuration

Configure exact proxy addresses or CIDR networks as a comma-separated value:

```env
TRUSTED_PROXIES=127.0.0.1,::1,10.0.0.0/8
```

The default is empty and trusts no proxy. Avoid trusting every address: forwarded headers are controlled by the client unless a known reverse proxy replaces them.

Both the standard `Forwarded` header and the common `X-Forwarded-For`, `X-Forwarded-Proto`, and `X-Forwarded-Host` headers are supported. `Forwarded` values take precedence when present.

## Registration

Register it before middleware that depends on the effective scheme or client address:

```php
use Atom\Http\TrustedProxyMiddleware;
use Atom\Security\SecurityHeadersMiddleware;

Route::get("/", $handler)->middleware([
    TrustedProxyMiddleware::class,
    SecurityHeadersMiddleware::class,
]);
```

Downstream code can use:

```php
$request->getClientIp();
$request->getScheme();
$request->getHost();
$request->isSecure();
```

Secure session-cookie detection and HSTS then use the normalized HTTPS state automatically.
