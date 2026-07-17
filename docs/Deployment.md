# Deployment

[Atom Framework](Index.md)

Atom targets the conventional PHP request lifecycle provided by PHP-FPM and Apache `mod_php`. Persistent application workers such as RoadRunner and Swoole are not currently supported.

## Production Checklist

Before deploying an application:

1. Point the web server document root at the application's `public/` directory. Never expose the project root.
2. Install production dependencies with `composer install --no-dev --classmap-authoritative`.
3. Set `APP_ENV=production` and `APP_DEBUG=false`.
4. Give the web-server user write access only to directories used for runtime data, such as `storage/cache`, `storage/logs`, an SQLite database, and the configured PHP session directory.
5. Run database migrations as a deployment step rather than from an HTTP request.
6. Configure HTTPS, trusted proxies, secure session cookies, and application-specific security headers.
7. Enable PHP OPcache and restart or reload PHP-FPM after deploying new code.

Do not use the Framework `DevReload` module in production.

## Nginx and PHP-FPM

The following server block serves static files directly and sends every other request to Atom's front controller. Adjust the domain, application path, PHP-FPM socket, and request-body limit for the deployment.

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com;

    root /var/www/atom/public;
    index index.php;
    client_max_body_size 10m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param HTTP_PROXY "";
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\. {
        deny all;
    }
}
```

Validate and reload Nginx after changing its configuration:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

PHP, Nginx, and Atom may each impose a request-body limit. Keep `client_max_body_size`, PHP's `post_max_size` and `upload_max_filesize`, and Atom's `RequestBodyLimitMiddleware` aligned.

## Apache and mod_php

Enable `mod_rewrite`, point the virtual host at `public/`, and allow the included `.htaccess` file to provide front-controller rewriting:

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/atom/public

    <Directory /var/www/atom/public>
        AllowOverride FileInfo
        Options -Indexes
        Require all granted
        DirectoryIndex index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/atom-error.log
    CustomLog ${APACHE_LOG_DIR}/atom-access.log combined
</VirtualHost>
```

On Debian-based systems, enable rewriting and reload Apache:

```bash
sudo a2enmod rewrite
sudo apachectl configtest
sudo systemctl reload apache2
```

If `.htaccess` files are disabled, put the equivalent rewrite rules directly inside the virtual-host `Directory` block.

## Environment

A minimal production environment starts with:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Lax
```

Keep `.env` outside `public/`, restrict its filesystem permissions, and never commit production secrets. When TLS terminates at a reverse proxy, configure only the proxy addresses or networks you control through `TRUSTED_PROXIES`; do not trust arbitrary forwarded headers.

See [Configuration](Configuration.md), [Sessions](Sessions.md), [Trusted Proxies](TrustedProxies.md), and [Security Headers](SecurityHeaders.md) for the corresponding application settings.

## Writable Data

Atom creates the file-cache directory when needed, but the PHP user must have permission to create and modify it. The same applies to a file logger and SQLite database. Prefer narrowly scoped ownership or access-control entries instead of making the entire project writable.

For a multi-server deployment, local files are not shared between hosts. Use shared infrastructure for sessions, cache, uploaded files, and application data when consistency across servers is required.

## OPcache

Production PHP should enable OPcache. A conservative starting point is:

```ini
opcache.enable=1
opcache.validate_timestamps=0
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

With timestamp validation disabled, reload PHP-FPM or Apache after each deployment so workers use the new code. Deployments that cannot guarantee a reload should keep timestamp validation enabled with an appropriate revalidation interval.

## Smoke Test

After deployment, verify:

- the home page and a nested application route return the expected status
- static CSS, JavaScript, and icon resources are served
- an unknown path reaches Atom and renders its 404 response
- `APP_DEBUG=false` prevents diagnostic details from appearing in error responses
- sessions and CSRF-protected forms work over HTTPS
- logs, cache entries, and database writes succeed without broad filesystem permissions

