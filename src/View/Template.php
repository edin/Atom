<?php

declare(strict_types=1);

namespace Atom\View;

final class Template
{
    public ?Template  $parent = null;
    public ?Template  $child = null;
    public ViewEngine $view;
    public string     $viewName;
    public string     $content = "";
    public array      $params = [];

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
