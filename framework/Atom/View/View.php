<?php

namespace Atom\View;

use Atom\Interfaces\IViewEngine;
use Atom\Interfaces\IViewInfo;
use Atom\Container\Container;

final class View
{
    private $viewsDir;
    private $di;
    private $engines = [];

    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    public function getDefaultExtension(): string
    {
        $extensions = array_keys($this->engines);
        if (isset($extensions[0])) {
            return $extensions[0];
        }
        return "";
    }

    public function setViewsDir(string $viewDir)
    {
        $this->viewsDir = $viewDir;
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
        $parameters['baseUrl'] = $this->di->Application->getBaseUrl();
        $parameters['container'] = $this->di;

        $viewEngine->setParams($parameters);
        return $viewEngine->render($view->getViewName(), $parameters);
    }

    public function getViewEngine(string $extension): IViewEngine
    {
        $viewEngine = $this->engines[$extension] ?? null;
        if ($viewEngine == null) {
            throw new \Exception("Can't find view engine for file type '{$extension}'");
        }
        return $this->di->{$viewEngine};
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