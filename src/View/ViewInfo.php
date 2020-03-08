<?php

namespace Atom\View;

use Atom\Interfaces\IViewInfo;

final class ViewInfo implements IViewInfo
{
    private $viewName;
    private $params;

    public function __construct(string $viewName, array $params = [])
    {
        $this->viewName = $viewName;
        $this->params = $params;
    }

    public function getViewName(): string
    {
        return $this->viewName;
    }

    public function getModel(): array
    {
        return $this->params;
    }
}
