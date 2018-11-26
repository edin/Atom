<?php

namespace Atom;

class ViewInfo {
    public $viewName;
    public $model;

    public function __construct(string $viewName, array $model = []) {
        $this->viewName = $viewName;
        $this->model = $model;
    }
}