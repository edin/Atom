<?php

declare(strict_types=1);

namespace Atom\Identity;

use SensitiveParameter;

interface IdentityProviderInterface
{
    public function findByIdentifier(string|int $identifier): ?IdentityInterface;

    public function findByLogin(string $login): ?IdentityInterface;

    public function validateCredentials(
        IdentityInterface $identity,
        #[SensitiveParameter] string $password
    ): bool;
}
