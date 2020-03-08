<?php

namespace Atom\View;

final class Template
{
    public $parent = null;
    public $child = null;
    public $view;
    public $viewName;
    public $content;
    public $params = [];

    public function __construct(ViewEngine $view, string $viewName, array $params)
    {
        $this->view = $view;
        $this->viewName = $viewName;
        $this->params = $params;
    }

    public function setParent(Template $parent)
    {
        $this->parent = $parent;
        $parent->child = $this;
    }

    public function render(): string
    {
        extract($this->view->getParams());
        extract($this->params);

        $view = $this->view;
        $content = $this->content;

        ob_start();
        include $this->viewName;
        $this->content = ob_get_contents();
        ob_end_clean();

        return $this->content;
    }
}
