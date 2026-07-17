<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Cache\CacheInterface;
use Atom\Http\MiddlewareInterface;
use Atom\Http\RateLimitMiddleware;
use Atom\Http\RateLimitOptions;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;

final readonly class LoginRateLimitMiddleware implements MiddlewareInterface
{
    private RateLimitMiddleware $middleware;

    public function __construct(CacheInterface $cache, AccountsOptions $options)
    {
        $this->middleware = new RateLimitMiddleware(
            $cache,
            new RateLimitOptions(
                maxAttempts: $options->loginMaxAttempts,
                windowSeconds: $options->loginWindowSeconds,
                keyPrefix: "accounts-login",
                includePath: false
            ),
            static function (Request $request): string {
                $ip = $request->getClientIp() === "" ? "unknown" : $request->getClientIp();
                $login = strtolower(trim($request->post()->string("login")));

                return $ip . "|" . hash("sha256", $login);
            }
        );
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        return $this->middleware->process($request, $handler);
    }
}
