<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Modules\Framework\Components\ToastModel;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/feedback", name: "showcase.components.feedback")]
final class FeedbackPage extends AppPage
{
    public string $title = "Feedback - Atom Showcase";

    #[State]
    public ToastModel $toast;

    public function __construct()
    {
        $this->toast = new ToastModel();
    }

    #[PageAction("showToast")]
    public function showToast(string $variant = "success"): void
    {
        $this->toast->open(
            "The page action completed and the toast was rendered by the server.",
            $variant,
            "Article saved"
        );
    }

    #[PageAction("showSnackBar")]
    public function showSnackBar(string $variant = "success"): void
    {
        $this->flash(
            "Article was updated. You can undo this action.",
            $variant
        );
    }
}
