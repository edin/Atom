<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Identity\AuthenticateMiddleware;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Modules\Accounts\Components\AccountsLayout;
use Atom\Modules\Accounts\Components\AccountsPanel;
use Atom\Modules\Accounts\Components\Button;
use Atom\Modules\Accounts\Components\Error;
use Atom\Modules\Accounts\Components\Field;
use Atom\Modules\Accounts\Components\LogoutForm;
use Atom\Modules\Accounts\Components\Message;
use Atom\Modules\Accounts\Middlewares\AccountsPageMiddleware;
use Atom\Modules\Accounts\Middlewares\ForgotPasswordRateLimitMiddleware;
use Atom\Modules\Accounts\Middlewares\LoginRateLimitMiddleware;
use Atom\Modules\Accounts\Middlewares\RegisterRateLimitMiddleware;
use Atom\Modules\Accounts\Middlewares\ResetPasswordRateLimitMiddleware;
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
        $context->component("Accounts.Layout", AccountsLayout::class);
        $context->component("Accounts.Panel", AccountsPanel::class);
        $context->component("Accounts.Field", Field::class);
        $context->component("Accounts.Button", Button::class);
        $context->component("Accounts.Error", Error::class);
        $context->component("Accounts.Message", Message::class);
        $context->component("Accounts.LogoutForm", LogoutForm::class);
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
