<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Pages;

use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Identity\AuthenticatorInterface;
use Atom\Modules\Accounts\AccountManagerInterface;
use Atom\Modules\Accounts\AccountsOptions;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Modules\Accounts\Middlewares\RegisterRateLimitMiddleware;
use Atom\Modules\Accounts\RegisterAccount;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Security\CsrfTokenManagerInterface;

#[PageRoute(
    "/register",
    name: "atom.accounts.register",
    middleware: RegisterRateLimitMiddleware::class,
    title: "Create account",
    description: "Display or submit the account registration form."
)]
final class RegisterPage extends AccountsPage
{
    private const TRANSPORT_FIELDS = [
        "_token",
        "_action",
        "_state",
        "login",
        "password",
        "password_confirmation",
    ];

    public string $action = "";
    public string $csrfToken = "";
    public string $loginUrl = "";
    public string $login = "";
    public string $error = "";

    public function __construct(
        private readonly AuthenticatorInterface $auth,
        private readonly AccountManagerInterface $accounts,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly AccountsOptions $options,
        private readonly AccountsRoutes $routes,
        private readonly Request $request
    ) {
    }

    public function template(): ?string
    {
        return $this->options->registerTemplate;
    }

    public function get(): ?Response
    {
        if ($this->auth->check()) {
            return $this->redirect($this->options->afterLogin);
        }

        $this->prepare($this->request->query()->string("login"));

        return null;
    }

    #[PageAction("register")]
    public function register(): ?Response
    {
        if ($this->auth->check()) {
            return $this->redirect($this->options->afterLogin);
        }

        $login = trim($this->request->post()->string("login"));
        $password = $this->request->post()->string("password");
        $confirmation = $this->request->post()->string("password_confirmation");
        $this->prepare($login);

        if ($login === "" || $password === "") {
            $this->error = "Enter a login and password.";
            return null;
        }

        if ($password !== $confirmation) {
            $this->error = "The password confirmation does not match.";
            return null;
        }

        $identity = $this->accounts->register(new RegisterAccount(
            $login,
            $password,
            $this->request->post()->except(self::TRANSPORT_FIELDS)
        ));
        if ($identity === null) {
            $this->error = "Account registration is not configured.";
            return null;
        }

        $this->auth->login($identity);

        return $this->redirect($this->options->afterLogin);
    }

    private function prepare(string $login): void
    {
        $this->preparePage("Create account", $this->routes);
        $this->action = $this->routes->register;
        $this->csrfToken = $this->csrf->token();
        $this->loginUrl = $this->routes->login;
        $this->login = $login;
    }

}
