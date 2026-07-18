<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\PageAction;

final class TabsModel
{
    public string $active = "";

    public function __construct(string $active = "")
    {
        $this->active = $active;
    }

    #[PageAction]
    public function select(string $name): void
    {
        $this->active = $name;
    }

    public function isActive(string $name): bool
    {
        return $this->active === $name;
    }
}
