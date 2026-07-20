<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Modules\Accounts\Components\AccountsLayout;
use Atom\Modules\Accounts\Components\AccountsPanel;
use Atom\Modules\Accounts\Components\Button;
use Atom\Modules\Accounts\Components\Error;
use Atom\Modules\Accounts\Components\Field;
use Atom\Modules\Accounts\Components\LogoutForm;
use Atom\Modules\Accounts\Components\Message;
use Atom\View\Component\ComponentSet;

final readonly class Accounts
{
    public static function module(?AccountsOptions $options = null): AccountsModule
    {
        return new AccountsModule($options);
    }

    public static function definitions(): ComponentSet
    {
        return ComponentSet::from([
            "Accounts.Layout" => AccountsLayout::class,
            "Accounts.Panel" => AccountsPanel::class,
            "Accounts.Field" => Field::class,
            "Accounts.Button" => Button::class,
            "Accounts.Error" => Error::class,
            "Accounts.Message" => Message::class,
            "Accounts.LogoutForm" => LogoutForm::class,
        ]);
    }
}
