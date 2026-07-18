<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Components;

use Atom\Modules\Accounts\Pages\AccountsPage;
use Atom\View\Component\Fragment;
use Atom\View\Component\TemplateComponent;
use Atom\View\Component\TemplateFragment;

final class AccountsLayout extends TemplateComponent
{
    public AccountsPage $page;
    public Fragment|TemplateFragment|null $content = null;
}
