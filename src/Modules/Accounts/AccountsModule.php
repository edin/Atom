<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Identity\AuthenticateMiddleware;
use Atom\Module\ModuleContext;
use Atom\Module\ModuleInterface;
use Atom\Modules\Accounts\UI\Components\LogoutForm;
use Atom\Modules\Accounts\UI\Pages\LoginPage;
use Atom\Page\PageRouteMetadata;
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

        $routes = new AccountsRoutes(
            $context->mountedPath("/login"),
            $context->mountedPath("/logout"),
            $context->resourcePath("/resources", "accounts.css")
        );
        $context->bindings->value(AccountsRoutes::class, $routes);

        $context->bind(AccountsPageMiddleware::class)->toSelf()->scoped();
        $context->bind(LoginRateLimitMiddleware::class)->toSelf()->scoped();
        $context->component("Accounts.LogoutForm", LogoutForm::class);
        $context->resources("/resources", __DIR__ . "/Resources");

        foreach ($context->pages(__DIR__ . "/UI/Pages") as $entry) {
            $metadata = $entry->getMetadataOfType(PageRouteMetadata::class);
            if (!$metadata instanceof PageRouteMetadata || $metadata->pageClass !== LoginPage::class) {
                continue;
            }

            $entry
                ->title("Sign in")
                ->description("Display or submit the account login form.")
                ->middleware(AccountsPageMiddleware::class);

            if ($entry->getMethod() === "POST") {
                $entry
                    ->middleware(CsrfMiddleware::class)
                    ->middleware(LoginRateLimitMiddleware::class);
            }
        }

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
