<?php

namespace Atom\View;

use Atom\Interfaces\IViewInfo;

final class ViewInfo implements IViewInfo
{
    private $viewName;
    private $model;

    public function __construct(string $viewName, array $model = [])
    {
        $this->viewName = $viewName;
        $this->model = $model;
    }

    public function getViewName(): string
    {
        return $this->viewName;
    }

    public function getModel()
    {
        return $this->model;
    }
}
