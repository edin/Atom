<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\UI\Pages;

use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Identity\AuthenticatorInterface;
use Atom\Modules\Accounts\AccountsOptions;
use Atom\Modules\Accounts\AccountsRedirects;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Page\Page;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Security\CsrfTokenManagerInterface;

#[PageRoute("/login", name: "atom.accounts.login")]
final class LoginPage extends Page
{
    public string $title = "Sign in";
    public string $action = "";
    public string $stylesheet = "";
    public string $csrfToken = "";
    public string $returnTo = "/";
    public string $login = "";
    public string $error = "";

    public function __construct(
        private readonly AuthenticatorInterface $auth,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly AccountsOptions $options,
        private readonly AccountsRoutes $routes,
        private readonly Request $request,
        private readonly AccountsRedirects $redirects = new AccountsRedirects()
    ) {
    }

    public function template(): ?string
    {
        return $this->options->loginTemplate;
    }

    public function get(): ?Response
    {
        if ($this->auth->check()) {
            return $this->redirect($this->redirects->local($this->options->afterLogin));
        }

        $this->prepare(
            $this->redirects->local($this->request->query()->string("return"), $this->options->afterLogin),
            $this->request->query()->string("login")
        );

        return null;
    }

    #[PageAction("login")]
    public function login(): ?Response
    {
        if ($this->auth->check()) {
            return $this->redirect($this->redirects->local($this->options->afterLogin));
        }

        $login = trim($this->request->post()->string("login"));
        $returnTo = $this->redirects->local(
            $this->request->post()->string("return"),
            $this->options->afterLogin
        );

        $this->prepare($returnTo, $login);

        if ($login === "" || !$this->auth->attempt($login, $this->request->post()->string("password"))) {
            $this->error = "The provided credentials are invalid.";
            return null;
        }

        return $this->redirect($returnTo);
    }

    private function prepare(string $returnTo, string $login): void
    {
        $this->title = $this->options->title;
        $this->action = $this->routes->login;
        $this->stylesheet = $this->routes->stylesheet;
        $this->csrfToken = $this->csrf->token();
        $this->returnTo = $returnTo;
        $this->login = $login;
    }

    private function redirect(string $target): Response
    {
        return (new Response())
            ->redirect($target)
            ->header("Cache-Control", "no-store");
    }
}
