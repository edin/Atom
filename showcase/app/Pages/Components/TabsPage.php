<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Modules\Components\TabsModel;
use Atom\Http\Request;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/tabs", name: "showcase.components.tabs")]
final class TabsPage extends AppPage
{
    public string $title = "Tabs - Atom Showcase";

    public string $tab = "overview";

    #[State]
    public TabsModel $localTabs;

    public function __construct()
    {
        $this->localTabs = new TabsModel("preview");
    }

    public function get(Request $request): void
    {
        $tab = $request->query()->string("tab", "overview");
        $this->tab = in_array($tab, ["overview", "drafts", "settings"], true) ? $tab : "overview";
    }
}
