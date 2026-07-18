<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Middlewares;

use Atom\Cache\CacheInterface;
use Atom\Http\Request;
use Atom\Modules\Accounts\AccountsOptions;

final readonly class ResetPasswordRateLimitMiddleware extends AccountsRateLimitMiddleware
{
    public function __construct(CacheInterface $cache, AccountsOptions $options)
    {
        parent::__construct(
            $cache,
            $options->resetPasswordMaxAttempts,
            $options->resetPasswordWindowSeconds,
            "accounts-reset-password",
            static function (Request $request): string {
                $token = $request->post()->string("token");

                return self::client($request) . "|" . hash("sha256", $token);
            }
        );
    }
}
