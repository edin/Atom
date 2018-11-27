<?php

namespace Atom\View;

use App\View\View;

class PhpViewEngine implements \Atom\Interfaces\IViewEngine
{
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function render(string $templatePath, array $params = []): string
    {
        return $this->renderFile($viewName, $params);
    }

    private function renderFile(string $__viewName__, array $__params__)
    {

        ob_start();
        extract($model);
        include $this->view->resolvePath($__viewName__);
        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    }
}
