<?php

declare(strict_types=1);

namespace Atom\Security;

use Atom\Session\SessionInterface;

final readonly class CsrfTokenManager implements CsrfTokenManagerInterface
{
    private const SESSION_KEY = "_atom_csrf_token";

    public function __construct(private SessionInterface $session)
    {
    }

    public function token(): string
    {
        $token = $this->currentToken();
        return $token ?? $this->refresh();
    }

    public function refresh(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->put(self::SESSION_KEY, $token);
        return $token;
    }

    public function validate(?string $token): bool
    {
        $current = $this->currentToken();
        return $current !== null && $token !== null && hash_equals($current, $token);
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    private function currentToken(): ?string
    {
        $token = $this->session->get(self::SESSION_KEY);
        return is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) === 1 ? $token : null;
    }
}
