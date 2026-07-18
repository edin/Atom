<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Identity\IdentityInterface;
use SensitiveParameter;

interface AccountManagerInterface
{
    public function register(RegisterAccount $account): ?IdentityInterface;

    public function requestPasswordReset(string $login): void;

    public function resetPassword(
        string $login,
        string $token,
        #[SensitiveParameter] string $password
    ): bool;
}
