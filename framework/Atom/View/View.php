<?php

namespace Atom;

use Latte\Engine;

final class View
{
    private $defaultExt = "";
    private $viewDir;
    private $engines = [];

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

    public function render() {
        //TODO: Render view somehow
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