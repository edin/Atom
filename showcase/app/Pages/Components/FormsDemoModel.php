<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;

final class FormsDemoModel
{
    #[Required("Title is required.")]
    #[MaxLength(80)]
    public string $title = "Component story";

    #[Required("Summary is required.")]
    #[MaxLength(160)]
    public string $summary = "Forms can bind to page properties or a form model.";

    public int $categoryId = 2;

    public bool $published = true;
}
