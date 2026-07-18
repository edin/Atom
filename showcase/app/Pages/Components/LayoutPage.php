<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Modules\Components\SidePanelModel;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/layout", name: "showcase.components.layout")]
final class LayoutPage extends AppPage
{
    public string $title = "Layout - Atom Showcase";

    #[State]
    public SidePanelModel $editor;

    public function __construct()
    {
        $this->editor = new SidePanelModel();
    }
}
