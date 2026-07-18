<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\PageAction;

final class DialogModel
{
    public bool $show = false;
    public mixed $value = null;

    #[PageAction]
    public function open(mixed $value = null): void
    {
        $this->show = true;
        $this->value = $value;
    }

    #[PageAction]
    public function close(): void
    {
        $this->show = false;
    }
}
