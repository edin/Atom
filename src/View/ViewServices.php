<?php

declare(strict_types=1);

namespace Atom\View;

use Atom\Di\Bindings;
use Atom\Di\ServiceProviderInterface;
use ReflectionClass;
use function dirname;

final class ViewServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(View::class)
            ->toFactory(function ($injector) {
                $app = $injector->get(\Atom\Application::class);
                $reflection = new ReflectionClass($app);
                $viewsDir = dirname($reflection->getFileName()) . "/Views";

                $view = new View($injector, $app);
                $view->setViewsDir($viewsDir);
                $view->setEngines([
                    ".php" => ViewEngine::class,
                ]);
                return $view;
            })
            ->singleton();

        $bindings->bind(ViewEngine::class)
            ->toSelf()
            ->scoped();
    }
}
