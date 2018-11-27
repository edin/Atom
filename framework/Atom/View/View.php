<?php

namespace Atom\View;

use Atom\Interfaces\{IViewInfo, IViewEngine};

final class View
{
    private $defaultExt = "";
    private $viewDir;
    private $dependencyContainer;
    private $engines = [];

    public function __construct($di) {
        $this->dependencyContainer = $di;
    }

    public function setDefaultExt(string $extension) {
        $this->defaultExt = $extension;
    }

    public function getDefaultExt(): string {
        return $this->defaultExt;
    }

    public function setViewDir(string $viewDir) {
        $this->viewDir = $viewDir;
    }

    public function getViewDir(): string {
        return $this->viewDir;
    }

    public function render(IViewInfo $view): string {

        $path = $this->resolvePath($view->getViewName());
        $ext  = \pathinfo($path, \PATHINFO_EXTENSION);

        $viewEngine = $this->getViewEngine($ext);

        return $viewEngine->render($path, $view->getModel());
    }

    public function getViewEngine(string $extension): IViewEngine
    {
        $viewEngine = $this->engines[$extension] ?? null;

        if ($viewEngine == null) {
            throw new \Exception("Can't find view engine for file type '{$extension}'");
        }

        return $this->dependencyContainer->{$viewEngine};
    }

    public function setEngines(array $engines) {
        $this->engines = $engines;
    }

    public function resolvePath(string $viewName): string {

        $ext = \pathinfo($viewName, \PATHINFO_EXTENSION);
        if ($ext == "") {
            $viewName = $viewName . $this->defaultExt;
        }

        $ext = \pathinfo($viewName, \PATHINFO_EXTENSION);

        $viewDir = rtrim($this->viewDir, " /");
        $viewName  = ltrim($viewName, " /");

        return $viewDir . "/" . $viewName;
    }
}