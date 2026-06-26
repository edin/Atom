<?php

declare(strict_types=1);

namespace App\Pages;

use App\Components\Layout;
use Atom\Page\Page;

abstract class AppPage extends Page
{
    public ?string $layout = Layout::class;

    public string $title = "Atom Sample";
}
