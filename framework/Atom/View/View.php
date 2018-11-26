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
        // $appDir   = dirname(dirname(__DIR__));
        // $cacheDir = $appDir . '/resource/cache';

        //TODO: Render view somehow
    }

    public function setEngines(array $engines) {
        $this->engines = $engines;
    }
}