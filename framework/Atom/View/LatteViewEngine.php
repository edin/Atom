<?php

class LatteViewProcessor implements \Atom\Interfaces\ViewEngineInterface
{
    public $cachePath;
    public $viewPath;

    private $view;

    public function __construct(\App\View\View $view) {
        $this->view = $view;
    }

    public function render(string $templatePath, array $model): string {

        $latte = new \Latte\Engine;
        $latte->setTempDirectory($this->cachePath);

        return $latte->renderToString($templatePath, $model);
    }
}