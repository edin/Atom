<?php

declare(strict_types=1);

namespace Atom\Database\Lock;

final class DatabaseLock
{
    private bool $released = false;

    public function __construct(private mixed $release)
    {
    }

    public function release(): void
    {
        if ($this->released) {
            return;
        }

        ($this->release)();
        $this->released = true;
    }

    public function __destruct()
    {
        $this->release();
    }
}
