<?php

namespace Atom\Router;

use function ltrim;
use function rtrim;

final class Route {
    public $name;
    public $group;
    public $method;
    public $path;
    public $handler;

    public function withName($name): Route {
        $this->name = $name;
        return $this;
    }

    public function getFullPath(): string {
        $prefixPath = ($this->group) ? $this->group->getPrefixPath() : "";
        $prefixPath = rtrim($prefixPath, " /");

        if ($prefixPath != "") {
            $routePath  = ltrim($this->path, " /");
            $result = $prefixPath . "/" . $routePath;
        } else {
            $result = $this->path;
        }

        return $result;
    }
}