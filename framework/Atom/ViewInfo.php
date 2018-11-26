<?php

namespace Atom;

class ViewInfo {
    public $viewName;
    public $model;

    public function __construct($viewName, $model) {
        $this->viewName = $viewName;
        $this->model = $model;
    }
}