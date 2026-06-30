<?php

declare(strict_types=1);

namespace Atom\Module;

final class ModuleRegistry
{
    /** @var list<ModuleRegistration> */
    private array $registrations = [];

    public function add(ModuleInterface $module, string $basePath = ""): self
    {
        $this->registrations[] = new ModuleRegistration($module, $basePath);

        return $this;
    }

    /**
     * @return list<ModuleRegistration>
     */
    public function all(): array
    {
        return $this->registrations;
    }
}
