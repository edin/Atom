<?php

declare(strict_types=1);

namespace Atom\Identity;

interface IdentityInterface
{
    public function identifier(): string|int;
}
