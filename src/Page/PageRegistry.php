<?php

declare(strict_types=1);

namespace Atom\Page;

final class PageRegistry
{
    /** @var list<PageDirectory> */
    private array $directories = [];

    public function directory(string $directory, string $pathPrefix = ""): self
    {
        $this->directories[] = new PageDirectory($directory, $pathPrefix);

        return $this;
    }

    /**
     * @return list<PageDirectory>
     */
    public function directories(): array
    {
        return $this->directories;
    }
}
