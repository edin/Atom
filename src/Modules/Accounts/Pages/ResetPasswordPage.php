<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Pages;

use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Modules\Accounts\AccountManagerInterface;
use Atom\Modules\Accounts\AccountsOptions;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Modules\Accounts\Middlewares\ResetPasswordRateLimitMiddleware;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Security\CsrfTokenManagerInterface;

#[PageRoute(
    "/reset-password",
    name: "atom.accounts.reset-password",
    middleware: ResetPasswordRateLimitMiddleware::class,
    title: "Reset password",
    description: "Display or submit the password reset form."
)]
final class ResetPasswordPage extends AccountsPage
{
    public string $action = "";
    public string $csrfToken = "";
    public string $loginUrl = "";
    public string $login = "";
    public string $token = "";
    public string $error = "";

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
        return $this->options->resetPasswordTemplate;
    }

    public function get(): void
    {
        $this->prepare(
            $this->request->query()->string("login"),
            $this->request->query()->string("token")
        );

        if ($this->token === "") {
            $this->error = "This password reset link is invalid or incomplete.";
        }
    }

    #[PageAction("resetPassword")]
    public function resetPassword(): ?Response
    {
        $login = trim($this->request->post()->string("login"));
        $token = $this->request->post()->string("token");
        $password = $this->request->post()->string("password");
        $confirmation = $this->request->post()->string("password_confirmation");
        $this->prepare($login, $token);

        if ($login === "" || $token === "" || $password === "") {
            $this->error = "The reset request is incomplete.";
            return null;
        }

        if ($password !== $confirmation) {
            $this->error = "The password confirmation does not match.";
            return null;
        }

        if (!$this->accounts->resetPassword($login, $token, $password)) {
            $this->error = "Password reset is not configured.";
            return null;
        }

        return $this->redirect($this->routes->login . "?login=" . rawurlencode($login));
    }

    private function prepare(string $login, string $token): void
    {
        $this->preparePage("Reset password", $this->routes);
        $this->action = $this->routes->resetPassword;
        $this->csrfToken = $this->csrf->token();
        $this->loginUrl = $this->routes->login;
        $this->login = $login;
        $this->token = $token;
    }

}
