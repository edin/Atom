<?php

declare(strict_types=1);

namespace Atom;

final class ApplicationBootstrappers
{
    /** @var list<ApplicationBootstrapper> */
    private array $bootstrappers = [];

    public function add(ApplicationBootstrapper $bootstrapper): self
    {
        $this->bootstrappers[] = $bootstrapper;

        return $this;
    }

    /**
     * @return list<ApplicationBootstrapper>
     */
    public function all(): array
    {
        return $this->bootstrappers;
    }
}
