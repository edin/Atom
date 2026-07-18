<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Pages;

use Atom\Http\Request;
use Atom\Modules\Accounts\AccountManagerInterface;
use Atom\Modules\Accounts\AccountsOptions;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Modules\Accounts\Middlewares\ForgotPasswordRateLimitMiddleware;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Security\CsrfTokenManagerInterface;

#[PageRoute(
    "/forgot-password",
    name: "atom.accounts.forgot-password",
    middleware: ForgotPasswordRateLimitMiddleware::class,
    title: "Forgot password",
    description: "Request password reset instructions."
)]
final class ForgotPasswordPage extends AccountsPage
{
    public string $action = "";
    public string $csrfToken = "";
    public string $loginUrl = "";
    public string $login = "";
    public string $error = "";
    public string $message = "";

    public function __construct(
        private readonly AccountManagerInterface $accounts,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly AccountsOptions $options,
        private readonly AccountsRoutes $routes,
        private readonly Request $request
    ) {
    }

    public function template(): ?string
    {
        return $this->options->forgotPasswordTemplate;
    }

    public function get(): void
    {
        $this->prepare($this->request->query()->string("login"));
    }

    #[PageAction("sendResetLink")]
    public function sendResetLink(): void
    {
        $login = trim($this->request->post()->string("login"));
        $this->prepare($login);

        if ($login === "") {
            $this->error = "Enter your email address.";
            return;
        }

        $this->accounts->requestPasswordReset($login);
        $this->message = "If an account matches that address, a password reset link will be sent.";
    }

    private function prepare(string $login): void
    {
        $this->preparePage("Forgot password", $this->routes);
        $this->action = $this->routes->forgotPassword;
        $this->csrfToken = $this->csrf->token();
        $this->loginUrl = $this->routes->login;
        $this->login = $login;
    }

}
