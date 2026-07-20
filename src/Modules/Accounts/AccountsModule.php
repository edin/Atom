<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Identity\AuthenticateMiddleware;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Modules\Accounts\Middlewares\AccountsPageMiddleware;
use Atom\Modules\Accounts\Middlewares\ForgotPasswordRateLimitMiddleware;
use Atom\Modules\Accounts\Middlewares\LoginRateLimitMiddleware;
use Atom\Modules\Accounts\Middlewares\RegisterRateLimitMiddleware;
use Atom\Modules\Accounts\Middlewares\ResetPasswordRateLimitMiddleware;
use Atom\Modules\Accounts\Jobs\SendPasswordResetJob;
use Atom\Router\RouteEntry;
use Atom\Security\CsrfMiddleware;

final readonly class AccountsModule implements ModuleInterface
{
    public function __construct(private ?AccountsOptions $options = null)
    {
    }

    public function register(ModuleContext $context): void
    {
        $options = $this->options ?? $context->config->options(AccountsOptions::class);
        $context->config->set($options);
        $context->bind(AccountsPublishBundle::class)->toSelf()->singleton();
        $context->commands(__DIR__ . "/Commands", __NAMESPACE__ . "\\Commands");
        $context->jobs(SendPasswordResetJob::class);

        if (!$context->bindings->has(AccountManagerInterface::class)) {
            $context->bind(AccountManagerInterface::class)
                ->to(NullAccountManager::class)
                ->singleton();
        }

        $routes = new AccountsRoutes(
            $context->mountedPath("/login"),
            $context->mountedPath("/logout"),
            $context->mountedPath("/register"),
            $context->mountedPath("/forgot-password"),
            $context->mountedPath("/reset-password"),
            $context->resourcePath("/resources", "accounts.css")
        );
        $context->bindings->value(AccountsRoutes::class, $routes);

        $context->bind(AccountsPageMiddleware::class)->toSelf()->scoped();
        $context->bind(LoginRateLimitMiddleware::class)->toSelf()->scoped();
        $context->bind(RegisterRateLimitMiddleware::class)->toSelf()->scoped();
        $context->bind(ForgotPasswordRateLimitMiddleware::class)->toSelf()->scoped();
        $context->bind(ResetPasswordRateLimitMiddleware::class)->toSelf()->scoped();
        $context->importComponents(Accounts::definitions());
        $context->resources("/resources", __DIR__ . "/Resources");

        $context->pages(__DIR__ . "/Pages", [
            AccountsPageMiddleware::class,
            CsrfMiddleware::class,
        ]);

        $context->route(
            RouteEntry::post($routes->logout, [LogoutHandler::class, "logout"])
                ->middleware(CsrfMiddleware::class)
                ->middleware(AuthenticateMiddleware::class)
                ->name("atom.accounts.logout")
                ->title("Sign out")
                ->description("End the authenticated session.")
        );
    }
}
