<?php

declare(strict_types=1);

namespace Showcase;

use Atom\Module\ModuleRegistry;
use Atom\Modules\Client\Client;
use Atom\Modules\Components\Components;
use Atom\Modules\DevReload\DevReloadModule;
use Atom\Page\PageRegistry;
use Atom\View\Component\ComponentRegistry;
use Showcase\Components\ComponentExample;

final class Application extends \Atom\Application
{
    protected function rootPath(): string
    {
        return dirname(__DIR__);
    }

    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(Client::module());
        $modules->add(Components::module());
        $modules->add(new DevReloadModule([
            "@root/app",
            "@root/public",
            "@root/../src",
        ]), "/atom/dev");
    }

    protected function pages(PageRegistry $pages): void
    {
        $pages->directory("@app/Pages");
    }

    protected function components(ComponentRegistry $components): void
    {
        $components->register("ComponentExample", ComponentExample::class);
    }
}
