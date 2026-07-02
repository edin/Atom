<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/data", name: "showcase.components.data")]
final class DataPage extends AppPage
{
    public string $title = "Data - Atom Showcase";

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $users = [
        ["name" => "Ada Lovelace", "role" => "Admin", "status" => "Active"],
        ["name" => "Grace Hopper", "role" => "Editor", "status" => "Active"],
        ["name" => "Margaret Hamilton", "role" => "Author", "status" => "Draft"],
    ];
}
