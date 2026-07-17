<?php

declare(strict_types=1);

namespace Atom\Identity;

use Atom\Session\SessionInterface;
use SensitiveParameter;

final class SessionAuthenticator implements AuthenticatorInterface
{
    public const SESSION_KEY = "_atom_identity";

    private bool $resolved = false;
    private ?IdentityInterface $identity = null;

    public function __construct(
        private readonly IdentityProviderInterface $identities,
        private readonly SessionInterface $session
    ) {
    }

    public function identity(): ?IdentityInterface
    {
        if ($this->resolved) {
            return $this->identity;
        }

        $this->resolved = true;
        $identifier = $this->session->get(self::SESSION_KEY);
        if (!is_string($identifier) && !is_int($identifier)) {
            if ($identifier !== null) {
                $this->session->remove(self::SESSION_KEY);
            }
            return null;
        }

        $this->identity = $this->identities->findByIdentifier($identifier);
        if ($this->identity === null) {
            $this->session->remove(self::SESSION_KEY);
        }

        return $this->identity;
    }

    public function check(): bool
    {
        return $this->identity() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function attempt(string $login, #[SensitiveParameter] string $password): bool
    {
        $identity = $this->identities->findByLogin($login);
        if ($identity === null || !$this->identities->validateCredentials($identity, $password)) {
            return false;
        }

        $this->login($identity);
        return true;
    }

    public function login(IdentityInterface $identity): void
    {
        $this->session->regenerate(true);
        $this->session->put(self::SESSION_KEY, $identity->identifier());
        $this->identity = $identity;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $this->session->remove(self::SESSION_KEY);
        $this->session->regenerate(true);
        $this->identity = null;
        $this->resolved = true;
    }

    public function refresh(): ?IdentityInterface
    {
        $this->identity = null;
        $this->resolved = false;
        return $this->identity();
    }
}
