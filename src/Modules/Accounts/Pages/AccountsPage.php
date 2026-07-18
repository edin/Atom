<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Pages;

use Atom\Http\Response;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Modules\Accounts\Components\AccountsLayout;
use Atom\Page\Page;

abstract class AccountsPage extends Page
{
    public ?string $layout = AccountsLayout::class;
    public string $title = "Account";
    public string $stylesheet = "";

    protected function preparePage(string $title, AccountsRoutes $routes): void
    {
        $this->title = $title;
        $this->stylesheet = $routes->stylesheet;
    }

    protected function redirect(string $target): Response
    {
        return (new Response())
            ->redirect($target)
            ->header("Cache-Control", "no-store");
    }
}
