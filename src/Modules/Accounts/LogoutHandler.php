<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Http\Response;
use Atom\Identity\AuthenticatorInterface;

final readonly class LogoutHandler
{
    public function __construct(
        private AuthenticatorInterface $auth,
        private AccountsOptions $options,
        private AccountsRedirects $redirects = new AccountsRedirects()
    ) {
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return (new Response())
            ->redirect($this->redirects->local($this->options->afterLogout))
            ->header("Cache-Control", "no-store");
    }
}
