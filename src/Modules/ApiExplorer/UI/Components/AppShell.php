<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer\UI\Components;

use Atom\View\Component\Fragment;
use Atom\View\Component\TemplateComponent;

final class AppShell extends TemplateComponent
{
    public string $title = "API Explorer";
    public string $resourcePath = "/atom/api/resources";
    public int $count = 0;
    public ?Fragment $content = null;

    public function operationLabel(): string
    {
        return $this->count . " operation" . ($this->count === 1 ? "" : "s");
    }
}
