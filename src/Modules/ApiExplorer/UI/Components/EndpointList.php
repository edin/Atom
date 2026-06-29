<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer\UI\Components;

use Atom\Modules\ApiExplorer\UI\Models\ApiOperationDescriptor;
use Atom\View\Component\TemplateComponent;

final class EndpointList extends TemplateComponent
{
    /** @var ApiOperationDescriptor[] */
    public array $operations = [];
    public int $selectedIndex = 0;

    public function linkFor(int $index): string
    {
        return "?id=" . $index;
    }

    public function itemClass(int $index): string
    {
        return $index === $this->selectedIndex ? "selected" : "";
    }
}
