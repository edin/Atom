<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\PageRoute;
use Showcase\Pages\AppPage;

#[PageRoute("/components/surfaces", name: "showcase.components.surfaces")]
final class SurfacesPage extends AppPage
{
    public string $title = "Surfaces - Atom Showcase";

    /**
     * @var array<int, array{title: string, status: string, updated: string}>
     */
    public array $articles = [
        ["title" => "Building Atom", "status" => "Draft", "updated" => "Today"],
        ["title" => "Component showcase", "status" => "Published", "updated" => "Yesterday"],
        ["title" => "Admin workflow", "status" => "Review", "updated" => "Jun 28"],
    ];

    /**
     * @var array<int, array{title: string, description: string, icon: string, badge?: string}>
     */
    public array $activity = [
        [
            "title" => "Article created",
            "description" => "Building Atom was saved as a draft.",
            "icon" => "@app/Resources/icons/article.svg",
        ],
        [
            "title" => "Traffic changed",
            "description" => "Views increased by 8% this week.",
            "icon" => "@app/Resources/icons/chart.svg",
            "badge" => "+8%",
        ],
        [
            "title" => "Comment needs review",
            "description" => "A reader left a new comment.",
            "icon" => "@app/Resources/icons/comment.svg",
        ],
    ];
}
