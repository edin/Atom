<?php

namespace Atom\View;

use Atom\View\View;

class LatteViewEngine implements \Atom\Interfaces\IViewEngine
{
    public $cachePath;
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function render(string $viewPath, array $params = []): string
    {
        $latte = new \Latte\Engine;
        $latte->setTempDirectory($this->cachePath);
        return $latte->renderToString($viewPath, $params);
    }
}