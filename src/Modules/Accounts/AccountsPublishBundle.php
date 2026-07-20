<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Publish\PublishBundle;

final readonly class AccountsPublishBundle
{
    public function bundle(): PublishBundle
    {
        return (new PublishBundle("accounts", __DIR__ . "/Publish"))
            ->file("Models/User.php", "@app/Models/User.php")
            ->file("Models/PasswordResetToken.php", "@app/Models/PasswordResetToken.php")
            ->file("Identity/AppIdentityProvider.php", "@app/Identity/AppIdentityProvider.php")
            ->file("Accounts/AccountManager.php", "@app/Accounts/AccountManager.php")
            ->file("Providers/AccountsServiceProvider.php", "@app/Providers/AccountsServiceProvider.php")
            ->file(
                "Migrations/M0001_create_users.php",
                "@migrations/M0001_create_users.php"
            )
            ->file(
                "Migrations/M0002_create_password_reset_tokens.php",
                "@migrations/M0002_create_password_reset_tokens.php"
            );
    }
}
