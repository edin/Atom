<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/alerts", name: "showcase.components.alerts")]
final class AlertsPage extends AppPage
{
    public string $title = "Alerts - Atom Showcase";
}
