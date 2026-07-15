<?php

declare(strict_types=1);

namespace Atom\Http;

use InvalidArgumentException;

final readonly class CorsMiddleware implements MiddlewareInterface
{
    /** @var string[] */
    private array $origins;
    /** @var string[] */
    private array $methods;
    /** @var string[] */
    private array $headers;
    /** @var string[] */
    private array $exposedHeaders;

    public function __construct(private CorsOptions $options)
    {
        $this->origins = $this->list($options->allowedOrigins);
        $this->methods = array_map("strtoupper", $this->list($options->allowedMethods));
        $this->headers = $this->list($options->allowedHeaders);
        $this->exposedHeaders = $this->list($options->exposedHeaders);

        if ($options->allowCredentials && in_array("*", $this->origins, true)) {
            throw new InvalidArgumentException("CORS wildcard origins cannot be combined with credentials.");
        }
        if ($options->maxAge < 0) {
            throw new InvalidArgumentException("CORS max age cannot be negative.");
        }
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $origin = trim($request->headers()->get("Origin", "") ?? "");
        if ($origin === "") {
            return $handler->handle($request);
        }

        $allowedOrigin = $this->allowedOrigin($origin);
        if ($this->isPreflight($request)) {
            return $this->preflight($request, $origin, $allowedOrigin);
        }

        $response = $handler->handle($request);
        if ($allowedOrigin === null) {
            return $response;
        }

        $this->applyOrigin($response, $allowedOrigin);
        if ($this->exposedHeaders !== []) {
            $this->setIfMissing($response, "Access-Control-Expose-Headers", implode(", ", $this->exposedHeaders));
        }

        return $response;
    }

    private function preflight(Request $request, string $origin, ?string $allowedOrigin): Response
    {
        $method = strtoupper(trim($request->headers()->get("Access-Control-Request-Method", "") ?? ""));
        $requestedHeaders = $this->list(
            $request->headers()->get("Access-Control-Request-Headers", "") ?? ""
        );

        if ($allowedOrigin === null || !in_array($method, $this->methods, true)
            || !$this->headersAllowed($requestedHeaders)) {
            return (new Response())->status(403)->content("CORS preflight request denied.");
        }

        $response = (new Response())->status(204);
        $this->applyOrigin($response, $allowedOrigin);
        $response->header("Access-Control-Allow-Methods", implode(", ", $this->methods));
        if ($requestedHeaders !== []) {
            $response->header("Access-Control-Allow-Headers", implode(", ", $requestedHeaders));
        }
        if ($this->options->maxAge > 0) {
            $response->header("Access-Control-Max-Age", (string) $this->options->maxAge);
        }
        $this->vary($response, "Access-Control-Request-Method");
        $this->vary($response, "Access-Control-Request-Headers");

        return $response;
    }

    private function applyOrigin(Response $response, string $origin): void
    {
        $this->setIfMissing($response, "Access-Control-Allow-Origin", $origin);
        if ($origin !== "*") {
            $this->vary($response, "Origin");
        }
        if ($this->options->allowCredentials) {
            $this->setIfMissing($response, "Access-Control-Allow-Credentials", "true");
        }
    }

    private function allowedOrigin(string $origin): ?string
    {
        if (in_array("*", $this->origins, true)) {
            return "*";
        }
        foreach ($this->origins as $allowed) {
            if (strcasecmp($allowed, $origin) === 0) {
                return $origin;
            }
        }
        return null;
    }

    private function isPreflight(Request $request): bool
    {
        return $request->getMethod() === "OPTIONS"
            && $request->headers()->has("Access-Control-Request-Method");
    }

    /** @param string[] $requested */
    private function headersAllowed(array $requested): bool
    {
        if (in_array("*", $this->headers, true)) {
            return true;
        }
        $allowed = array_map("strtolower", $this->headers);
        foreach ($requested as $header) {
            if (!in_array(strtolower($header), $allowed, true)) {
                return false;
            }
        }
        return true;
    }

    /** @return string[] */
    private function list(string $value): array
    {
        return array_values(array_filter(array_map("trim", explode(",", $value)), static fn(string $item): bool => $item !== ""));
    }

    private function setIfMissing(Response $response, string $name, string $value): void
    {
        if (!$response->headers()->has($name)) {
            $response->header($name, $value);
        }
    }

    private function vary(Response $response, string $name): void
    {
        $values = $this->list($response->headers()->get("Vary", "") ?? "");
        foreach ($values as $value) {
            if ($value === "*" || strcasecmp($value, $name) === 0) {
                return;
            }
        }
        $values[] = $name;
        $response->header("Vary", implode(", ", $values));
    }
}
