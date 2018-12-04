<?php

namespace Atom\View;

final class Template {
    public $parent = null;
    public $child = null;
    public $view;
    public $viewName;
    public $content;

    public function __construct(ViewEngine $view, string $viewName) {
        $this->view = $view;
        $this->viewName = $viewName;
    }

    public function setParent(Template $parent) {
        $this->parent = $parent;
        $parent->child = $this;
    }

    public function render(array $params = []): string {
        extract($params);

        $view = $this->view;
        $content = $this->content;

        ob_start();
        include($this->viewName);
        $this->content = ob_get_contents();
        ob_end_clean();

        return $this->content;
    }
}