<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Middlewares;

use Atom\Cache\CacheInterface;
use Atom\Http\Request;
use Atom\Modules\Accounts\AccountsOptions;

final readonly class ForgotPasswordRateLimitMiddleware extends AccountsRateLimitMiddleware
{
    public function __construct(CacheInterface $cache, AccountsOptions $options)
    {
        parent::__construct(
            $cache,
            $options->forgotPasswordMaxAttempts,
            $options->forgotPasswordWindowSeconds,
            "accounts-forgot-password",
            static function (Request $request): string {
                $login = strtolower(trim($request->post()->string("login")));

                return self::client($request) . "|" . hash("sha256", $login);
            }
        );
    }
}
