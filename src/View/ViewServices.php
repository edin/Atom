<?php

namespace Atom\View;

use Atom\Application;
use Atom\Container\Container;
use ReflectionClass;
use function dirname;

class ViewServices
{
    public function configureServices(Container $container, Application $app)
    {
        // TODO: Use configuration to resolve views directory or add getViewsDirectory() virtual method to application
        $reflection = new ReflectionClass($app);
        $viewsDir = dirname($reflection->getFileName()) . "/Views";

        $container->ViewEngine = ViewEngine::class;

        $container->bind(\Atom\View\View::class)
            ->withName("View")
            ->asShared()
            ->toFactory(function () use ($container, $viewsDir) {
                $view = new \Atom\View\View($container);
                $view->setViewsDir($viewsDir);
                $view->setEngines([
                    ".php" => "ViewEngine",
                ]);
                return $view;
            });
    }
}
