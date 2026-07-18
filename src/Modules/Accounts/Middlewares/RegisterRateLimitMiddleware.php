<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Middlewares;

use Atom\Cache\CacheInterface;
use Atom\Http\Request;
use Atom\Modules\Accounts\AccountsOptions;

final readonly class RegisterRateLimitMiddleware extends AccountsRateLimitMiddleware
{
    public function __construct(CacheInterface $cache, AccountsOptions $options)
    {
        parent::__construct(
            $cache,
            $options->registerMaxAttempts,
            $options->registerWindowSeconds,
            "accounts-register",
            static fn(Request $request): string => self::client($request)
        );
    }
}
