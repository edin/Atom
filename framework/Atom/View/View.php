<?php

namespace Atom\View;

use Atom\Interfaces\IViewEngine;
use Atom\Interfaces\IViewInfo;

final class View
{
    private $viewDir;
    private $dependencyContainer;
    private $engines = [];

    public function __construct($di)
    {
        $this->dependencyContainer = $di;
    }

    public function getDefaultExt(): string
    {
        $extensions = array_keys($this->engines);

        if (isset($extensions[0])) {
            return "." . $extensions[0];
        }

        return "";
    }

    public function setViewDir(string $viewDir)
    {
        $this->viewDir = $viewDir;
    }

    public function getViewDir(): string
    {
        return $this->viewDir;
    }

    public function render(IViewInfo $view): string
    {
        $path = $this->resolvePath($view->getViewName());
        $ext = \pathinfo($path, \PATHINFO_EXTENSION);

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

    public function setEngines(array $engines)
    {
        $this->engines = $engines;
    }

    public function resolvePath(string $viewName): string
    {
        $ext = \pathinfo($viewName, \PATHINFO_EXTENSION);
        if ($ext == "") {
            $viewName = $viewName . $this->getDefaultExt();
        }

        $ext = \pathinfo($viewName, \PATHINFO_EXTENSION);

        $viewDir = rtrim($this->viewDir, " /");
        $viewName = ltrim($viewName, " /");

        return $viewDir . "/" . $viewName;
    }
}
