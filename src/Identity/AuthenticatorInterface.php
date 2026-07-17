<?php

declare(strict_types=1);

namespace Atom\Identity;

use SensitiveParameter;

interface AuthenticatorInterface
{
    public function identity(): ?IdentityInterface;

    public function check(): bool;

    public function guest(): bool;

    public function attempt(string $login, #[SensitiveParameter] string $password): bool;

    public function login(IdentityInterface $identity): void;

    public function logout(): void;

    public function refresh(): ?IdentityInterface;
}
