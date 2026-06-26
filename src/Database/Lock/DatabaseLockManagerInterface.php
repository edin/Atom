<?php

declare(strict_types=1);

namespace Atom\Database\Lock;

interface DatabaseLockManagerInterface
{
    public function acquire(string $name): ?DatabaseLock;
}
