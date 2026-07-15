# Error Pages

Atom registers a standalone error pages module by default. It renders framework-generated `404` and `405` responses and unhandled exceptions without using the page engine, component library, JavaScript, or external assets.

When the optional Logging module provides a `LoggerInterface`, unhandled exceptions are recorded automatically before the response is rendered. Log context includes the error reference, configured request ID, method, path, effective client IP, matched route metadata, exception, and previous exception when available. The same exception object is logged at most once per request, and logging failures never prevent error-page rendering.

The default HTML document is a native PHP view with its own small stylesheet. The diagnostic panel is a second native PHP view. They are located under `src/Modules/ErrorPages/Views` and are rendered directly through an isolated output buffer rather than Atom's view engine. If the error handler cannot be resolved or a view throws while rendering, the application falls back to a plain-text `500 Internal Server Error` response.

## Configuration

Error diagnostics are disabled by default. Enable them locally with:

```env
APP_DEBUG=true
```

Normal status pages contain only a friendly message. Exception pages also contain an error reference, and debug pages show the request method and path, exception type, message, file, line, and trace.

Do not enable debug output in production.

## JSON Errors

Requests whose `Accept` header contains `application/json` or a `+json` media type receive JSON:

```json
{
  "error": {
    "status": 404,
    "title": "Page not found",
    "message": "The page you were looking for could not be found."
  }
}
```

Debug details are included under `error.debug` only when `APP_DEBUG` is enabled.

## HTTP Exceptions

Throw `HttpException` when an exception should intentionally produce a public HTTP response:

```php
use Atom\Modules\ErrorPages\HttpException;

throw new HttpException(
    429,
    "Please wait before trying again.",
    ["Retry-After" => "30"]
);
```

Unexpected exceptions always produce a generic `500` message outside debug mode.

## Customizing

The default module is registered before application modules. To customize it, copy the small module and handler classes into the application, change the HTML or behavior, and bind the same interface from an application module:

```php
use Atom\Modules\ErrorPages\ErrorPageHandlerInterface;

public function register(ModuleContext $context): void
{
    $context->bind(ErrorPageHandlerInterface::class)
        ->to(AppErrorPageHandler::class)
        ->scoped();
}
```

Register the application module normally from `Application::modules()`. Because it is registered after Atom's default, its handler is used for subsequent requests.

Custom handlers should remain independent of services that may have caused the original failure. Core always retains the final plain-text fallback.
