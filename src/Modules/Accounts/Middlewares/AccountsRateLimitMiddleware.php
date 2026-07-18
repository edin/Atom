<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Middlewares;

use Atom\Cache\CacheInterface;
use Atom\Http\MiddlewareInterface;
use Atom\Http\RateLimitMiddleware;
use Atom\Http\RateLimitOptions;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

abstract readonly class AccountsRateLimitMiddleware implements MiddlewareInterface
{
    private RateLimitMiddleware $middleware;

    /**
     * @param callable(Request): string $keyResolver
     */
    protected function __construct(
        CacheInterface $cache,
        int $maxAttempts,
        int $windowSeconds,
        string $keyPrefix,
        callable $keyResolver
    ) {
        $this->middleware = new RateLimitMiddleware(
            $cache,
            new RateLimitOptions(
                maxAttempts: $maxAttempts,
                windowSeconds: $windowSeconds,
                keyPrefix: $keyPrefix,
                includePath: false
            ),
            $keyResolver
        );
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($request->getMethod() !== "POST") {
            return $handler->handle($request);
        }

        return $this->middleware->process($request, $handler);
    }

    protected static function client(Request $request): string
    {
        return $request->getClientIp() === "" ? "unknown" : $request->getClientIp();
    }
}
