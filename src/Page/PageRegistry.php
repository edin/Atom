<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Http\MiddlewareInterface;

final class PageRegistry
{
    /** @var list<PageDirectory> */
    private array $directories = [];

    /**
     * @param array<class-string<MiddlewareInterface>|MiddlewareInterface> $middlewares
     */
    public function directory(string $directory, string $pathPrefix = "", array $middlewares = []): self
    {
        $this->directories[] = new PageDirectory($directory, $pathPrefix, $middlewares);

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
