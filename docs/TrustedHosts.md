# Trusted Hosts

`TrustedHostMiddleware` rejects malformed or unapproved `Host` headers before routing. This protects absolute URL generation, redirects, password-reset links, and future host-derived cache keys from Host-header poisoning.

## Configuration

```env
TRUSTED_HOSTS=example.com,*.example.com,localhost:8080,[::1]:8080
```

The empty default disables validation. Exact DNS names and IPv4 or bracketed IPv6 addresses are supported. An entry without a port accepts that host on any valid port; including a port restricts the match. `*.example.com` accepts subdomains but not the `example.com` apex, which must be listed separately when required.

## Registration order

Register trusted proxies before trusted hosts so the externally visible forwarded host is normalized before validation:

```php
use Atom\Http\MiddlewareRegistry;
use Atom\Http\TrustedHostMiddleware;
use Atom\Http\TrustedProxyMiddleware;

protected function middlewares(MiddlewareRegistry $middlewares): void
{
    $middlewares
        ->add(TrustedProxyMiddleware::class)
        ->add(TrustedHostMiddleware::class);
}
```

Only configure proxy networks you control. Otherwise an attacker could provide the forwarded host that is subsequently validated.
