<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Components;

use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Security\CsrfTokenManagerInterface;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class LogoutForm implements ComponentInterface
{
    public string $label = "Sign out";
    public string $class = "";

    public function __construct(
        private readonly AccountsRoutes $routes,
        private readonly CsrfTokenManagerInterface $csrf
    ) {
    }

    public function render(): string
    {
        return Html::tag("form", [
            "method" => "post",
            "action" => $this->action(),
            "class" => $this->class,
        ], Html::voidTag("input", [
            "type" => "hidden",
            "name" => CsrfTokenManagerInterface::FIELD_NAME,
            "value" => $this->token(),
        ]) . Html::tag("button", ["type" => "submit"], Html::escape($this->label)));
    }

    public function action(): string
    {
        return $this->routes->logout;
    }

    public function token(): string
    {
        return $this->csrf->token();
    }
}
