<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\PageAction;

final class ToastModel
{
    public bool $show = false;
    public string $title = "";
    public string $description = "";
    public string $text = "";
    public string $variant = "neutral";

    public function open(string $message, string $variant = "success", string $title = ""): void
    {
        $this->show = true;
        $this->text = "";
        $this->description = $message;
        $this->title = $title;
        $this->variant = $variant;
    }

    #[PageAction]
    public function close(): void
    {
        $this->show = false;
    }
}
