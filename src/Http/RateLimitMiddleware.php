<?php

declare(strict_types=1);

namespace Atom\Http;

use Atom\Cache\CacheException;
use Atom\Cache\CacheInterface;
use Closure;
use InvalidArgumentException;

final readonly class RateLimitMiddleware implements MiddlewareInterface
{
    private Closure $keyResolver;
    private Closure $clock;

    /**
     * @param null|callable(Request): string $keyResolver
     * @param null|callable(): int $clock
     */
    public function __construct(
        private CacheInterface $cache,
        private RateLimitOptions $options,
        ?callable $keyResolver = null,
        ?callable $clock = null
    ) {
        if ($options->maxAttempts < 0) {
            throw new InvalidArgumentException("Rate limit maximum attempts cannot be negative.");
        }
        if ($options->maxAttempts > 0 && $options->windowSeconds < 1) {
            throw new InvalidArgumentException("Rate limit window must be at least one second.");
        }
        if ($options->keyPrefix === "" || preg_match('/[\x00-\x1F\x7F]/', $options->keyPrefix) === 1) {
            throw new InvalidArgumentException("Rate limit key prefix cannot be empty or contain control characters.");
        }

        $this->keyResolver = $keyResolver === null
            ? fn(Request $request): string => $this->defaultIdentity($request)
            : Closure::fromCallable($keyResolver);
        $this->clock = $clock === null
            ? static fn(): int => time()
            : Closure::fromCallable($clock);
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->options->maxAttempts === 0) {
            return $handler->handle($request);
        }

        $now = ($this->clock)();
        $window = intdiv($now, $this->options->windowSeconds);
        $resetAt = ($window + 1) * $this->options->windowSeconds;
        $identity = ($this->keyResolver)($request);
        if ($identity === "") {
            throw new InvalidArgumentException("Rate limit key resolver returned an empty identity.");
        }
        $key = "rate-limit:" . $this->options->keyPrefix . ":" . hash("sha256", $identity . ":" . $window);

        try {
            $attempts = $this->cache->increment($key, 1, max(1, $resetAt - $now + 1));
        } catch (CacheException $exception) {
            if (!$this->options->failOpen) {
                throw $exception;
            }
            return $handler->handle($request);
        }

        $remaining = max(0, $this->options->maxAttempts - $attempts);
        if ($attempts > $this->options->maxAttempts) {
            return $this->withHeaders(
                (new Response())
                    ->status(429)
                    ->header("Content-Type", "text/plain; charset=utf-8")
                    ->header("Retry-After", (string) max(1, $resetAt - $now))
                    ->content("Too many requests."),
                $remaining,
                $resetAt
            );
        }

        return $this->withHeaders($handler->handle($request), $remaining, $resetAt);
    }

    private function defaultIdentity(Request $request): string
    {
        $parts = [$request->getClientIp() === "" ? "unknown" : $request->getClientIp()];
        if ($this->options->includeMethod) {
            $parts[] = $request->getMethod();
        }
        if ($this->options->includePath) {
            $parts[] = $request->getPath();
        }
        return implode("|", $parts);
    }

    private function withHeaders(Response $response, int $remaining, int $resetAt): Response
    {
        $headers = [
            "X-RateLimit-Limit" => (string) $this->options->maxAttempts,
            "X-RateLimit-Remaining" => (string) $remaining,
            "X-RateLimit-Reset" => (string) $resetAt,
        ];
        foreach ($headers as $name => $value) {
            if (!$response->headers()->has($name)) {
                $response->header($name, $value);
            }
        }
        return $response;
    }
}
