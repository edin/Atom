<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

interface HttpExceptionInterface
{
    public function status(): int;

    /**
     * @return array<string, string>
     */
    public function headers(): array;
}
