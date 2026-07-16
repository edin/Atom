<?php

declare(strict_types=1);

namespace App;

use App\Components\ConfirmDialog;
use App\Components\Table;
use App\Controllers\ApiController;
use Atom\Database\DatabaseServices;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderRegistry;
use Atom\Module\ModuleRegistry;
use Atom\Modules\ApiExplorer\ApiExplorer;
use Atom\Modules\Framework\Framework;
use Atom\Page\PageRegistry;
use Atom\Router\Route;
use Atom\View\Component\ComponentRegistry;

final class Application extends \Atom\Application
{
    protected string $baseUrl = "";

    protected function rootPath(): string
    {
        return dirname(__DIR__);
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(DatabaseServices::fromConfig($this->getConfig(), $this->getPaths()));
    }

    protected function modules(ModuleRegistry $modules): void
    {
        $modules
            ->add(Framework::module())
            ->add(ApiExplorer::module(), "/atom/api");
    }

    protected function pages(PageRegistry $pages): void
    {
        $pages->directory("@app/Pages");
    }

    protected function components(ComponentRegistry $components): void
    {
        $components
            ->register("Sample.Table", Table::class)
            ->register("ConfirmDialog", ConfirmDialog::class);
    }

    protected function bootstrap(Injector $injector): void
    {
        Route::attach(ApiController::class);
    }
}
