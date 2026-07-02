<?php

declare(strict_types=1);

namespace App\Pages;

use Atom\Page\PageAction;
use Atom\Page\PageRoute;

#[PageRoute("/components", name: "components")]
final class ComponentsPage extends AppPage
{
    public string $title = "Components - Atom Sample";

    public string $titleInput = "Hello Atom";

    public string $summary = "A compact form field with page binding.";

    public string $body = "Components are plain PHP objects rendered from atom.html tags.";

    public int $categoryId = 2;

    public bool $isPublished = true;

    public int $componentId = 42;

    /** @var list<object{id: int, name: string}> */
    public array $categories = [];

    public function get(): void
    {
        $this->categories = [
            (object) ["id" => 1, "name" => "News"],
            (object) ["id" => 2, "name" => "Framework"],
            (object) ["id" => 3, "name" => "Release notes"],
        ];
    }

    #[PageAction("preview")]
    public function preview(): void
    {
        $this->get();
    }
}
