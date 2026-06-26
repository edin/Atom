<?php

declare(strict_types=1);

namespace Atom\View;

use Atom\Application;
use Atom\Di\Injector;
use Atom\Interfaces\IViewEngine;
use Atom\Interfaces\IViewInfo;

final class View
{
    private string $viewsDir;
    private array $engines = [];

    public function __construct(private Injector $injector, private Application $app)
    {
    }

    public function getDefaultExtension(): string
    {
        $extensions = array_keys($this->engines);
        if (isset($extensions[0])) {
            return $extensions[0];
        }
        return "";
    }

    public function setViewsDir(string $viewsDir)
    {
        $this->viewsDir = $viewsDir;
    }

    public function getViewsDir(): string
    {
        return $this->viewsDir;
    }

    public function render(IViewInfo $view): string
    {
        $path = $this->resolvePath($view->getViewName());
        $ext = "." . \pathinfo($path, \PATHINFO_EXTENSION);

        $viewEngine = $this->getViewEngine($ext);

        $parameters = $view->getModel();
        $parameters['baseUrl'] = $this->app->getBaseUrl();
        $parameters['injector'] = $this->injector;

        $viewEngine->setParams($parameters);
        return $viewEngine->render($view->getViewName(), $parameters);
    }

    public function getViewEngine(string $extension): IViewEngine
    {
        $viewEngine = $this->engines[$extension] ?? null;
        if ($viewEngine == null) {
            throw new \RuntimeException("Can't find view engine for file type '{$extension}'");
        }
        return $this->injector->get($viewEngine);
    }

    public function setEngines(array $engines)
    {
        $this->engines = $engines;
    }

    public function resolvePath(string $viewName): string
    {
        $ext = \pathinfo($viewName, \PATHINFO_EXTENSION);
        if ($ext == "") {
            $viewName = $viewName . $this->getDefaultExtension();
        }

        $viewDir = rtrim($this->viewsDir, " /");
        $viewName = ltrim($viewName, " /");

        return $viewDir . "/" . $viewName;
    }
}
