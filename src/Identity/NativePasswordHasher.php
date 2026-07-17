<?php

declare(strict_types=1);

namespace Atom\Identity;

use SensitiveParameter;

final readonly class NativePasswordHasher implements PasswordHasherInterface
{
    /**
     * @param array<string, int|string> $options
     */
    public function __construct(
        private string|int $algorithm = PASSWORD_DEFAULT,
        private array $options = []
    ) {
    }

    public function hash(#[SensitiveParameter] string $password): string
    {
        return password_hash($password, $this->algorithm, $this->options);
    }

    public function verify(#[SensitiveParameter] string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }
}
