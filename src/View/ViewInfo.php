<?php

declare(strict_types=1);

namespace Atom\View;

use Atom\Interfaces\IViewInfo;

final class ViewInfo implements IViewInfo
{
    private string $theme = "";
    private string $directory = "";
    private string $viewName;
    private array $params;

    public function __construct(string $viewName, array $params = [])
    {
        $this->viewName = $viewName;
        $this->params = $params;
    }

    public function setDirectory(string $directory)
    {
        $this->directory = $directory;
    }

    public function setTheme(string $theme)
    {
        $this->theme = $theme;
    }

    public function getViewName(): string
    {
        //TODO: Use theme and directory to resolve view name
        return $this->viewName;
    }

    public function getModel(): array
    {
        return $this->params;
    }
}
