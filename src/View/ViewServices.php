<?php

namespace Atom\View;

use Atom\Container\Container;
use ReflectionClass;
use function dirname;

class ViewServices
{
    public function configureServices(Container $container)
    {
        if (!$container->has("Application")) {
            throw new \RuntimeException("Missing  Application");
        }

        // TODO: Use configuration to resolve views directory or add getViewsDirectory() virtual method to application
        $reflection = new ReflectionClass($container->Application);
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
