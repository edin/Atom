<?php

namespace Atom\View;

use Atom\Container\Container;

class ViewServices
{
    public function configureServices(Container $container)
    {
        $container->bind(\Atom\View\View::class)
            ->withName("View")
            ->asShared()
            ->toFactory(function () use ($container) {
                $view = new \Atom\View\View($container);
                $view->setViewsDir($container->Configuration->viewsDirectory);
                $view->setEngines([
                    ".php" => "ViewEngine",
                ]);
                return $view;
            });
    }
}
