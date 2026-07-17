<?php

declare(strict_types=1);

namespace Atom\Identity;

use SensitiveParameter;

interface PasswordHasherInterface
{
    public function hash(#[SensitiveParameter] string $password): string;

    public function verify(#[SensitiveParameter] string $password, string $hash): bool;

    public function needsRehash(string $hash): bool;
}
