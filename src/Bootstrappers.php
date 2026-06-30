<?php

declare(strict_types=1);

namespace Atom;

final class Bootstrappers
{
    /** @var list<Bootstrapper> */
    private array $bootstrappers = [];

    public function add(Bootstrapper $bootstrapper): self
    {
        $this->bootstrappers[] = $bootstrapper;

        return $this;
    }

    /**
     * @return list<Bootstrapper>
     */
    public function all(): array
    {
        return $this->bootstrappers;
    }
}
