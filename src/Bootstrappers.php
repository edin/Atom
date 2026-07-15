<?php

declare(strict_types=1);

namespace Atom;

final class Bootstrappers
{
    /** @var list<BootstrapperInterface> */
    private array $bootstrappers = [];

    public function add(BootstrapperInterface $bootstrapper): self
    {
        $this->bootstrappers[] = $bootstrapper;

        return $this;
    }

    /**
     * @return list<BootstrapperInterface>
     */
    public function all(): array
    {
        return $this->bootstrappers;
    }
}
