<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/dialogs", name: "showcase.components.dialogs")]
final class DialogsPage extends AppPage
{
    public string $title = "Dialogs - Atom Showcase";

    #[State]
    public string $openDialog = "";

    #[State]
    public string $lastAction = "No dialog action yet.";

    #[PageAction("openDialog")]
    public function openDialog(string $name): void
    {
        $this->openDialog = $name;
        $this->lastAction = "Opened {$name} dialog.";
    }

    #[PageAction("closeDialog")]
    public function closeDialog(): void
    {
        $this->openDialog = "";
        $this->lastAction = "Dialog closed.";
    }

    #[PageAction("confirmDelete")]
    public function confirmDelete(): void
    {
        $this->openDialog = "";
        $this->lastAction = "Article deleted.";
    }
}
