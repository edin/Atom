<?php

declare(strict_types=1);

namespace {{ namespace }};

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;

final class {{ class }} implements ComponentInterface
{
    public ?Fragment $content = null;

    public function render(): string
    {
        return $this->content?->render() ?? "";
    }
}
