<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts\Components;

use Atom\View\Component\Fragment;
use Atom\View\Component\TemplateComponent;
use Atom\View\Component\TemplateFragment;

final class AccountsPanel extends TemplateComponent
{
    public string $title = "Account";
    public string $description = "";
    public string $eyebrow = "Account";
    public Fragment|TemplateFragment|null $content = null;
}
