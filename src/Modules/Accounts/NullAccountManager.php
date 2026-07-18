<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Identity\IdentityInterface;
use SensitiveParameter;

final readonly class NullAccountManager implements AccountManagerInterface
{
    public function register(RegisterAccount $account): ?IdentityInterface
    {
        return null;
    }

    public function requestPasswordReset(string $login): void
    {
    }

    public function resetPassword(
        string $login,
        string $token,
        #[SensitiveParameter] string $password
    ): bool {
        return false;
    }
}
