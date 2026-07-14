<?php

declare(strict_types=1);

namespace Atom\Modules\DevReload;

final readonly class DevReloadOptions
{
    /**
     * @param string[] $watchPaths
     */
    public function __construct(public array $watchPaths)
    {
    }
}
