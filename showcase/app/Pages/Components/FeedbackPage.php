<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/feedback", name: "showcase.components.feedback")]
final class FeedbackPage extends AppPage
{
    public string $title = "Feedback - Atom Showcase";

    #[PageAction("showToast")]
    public function showToast(string $variant = "success"): void
    {
        $this->flash(
            "The page action completed and the toast was rendered by the server.",
            $variant,
            "Article saved"
        );
    }
}
