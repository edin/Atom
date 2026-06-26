<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Closure;

final readonly class Fragment
{
    /**
     * @param Closure(array<string, mixed>): string $renderer
     */
    public function __construct(private Closure $renderer)
    {
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(array $variables = []): string
    {
        return ($this->renderer)($variables);
    }
}
