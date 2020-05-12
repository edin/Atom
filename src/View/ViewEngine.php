<?php

declare(strict_types=1);

namespace Atom\View;

use SplStack;

final class ViewEngine implements \Atom\Interfaces\IViewEngine
{
    public array $sections = [];
    public array $prevSections = [];
    private SplStack $sectionStack;
    private View $view;
    private array $params = [];
    private Template $currentTemplate;

    public function __construct(View $view)
    {
        $this->view = $view;
        $this->sectionStack = new \SplStack();
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getSectionPlacholder(string $name): string
    {
        return "<!-- {Section($name)} -->";
    }

    public function section($name)
    {
        $prevSection = $this->prevSections[$name] ?? null;
        $sectionContent = $this->sections[$name]->content ?? "";

        if ($prevSection) {
            $placeHolder = $this->getSectionPlacholder($name);
            $sectionContent = str_replace($placeHolder, $sectionContent, $prevSection->content);
        }

        return $sectionContent;
    }

    public function start($name)
    {
        if (isset($this->sections[$name])) {
            $this->prevSections[$name] = $this->sections[$name];
        }
        $section = new \stdClass;
        $section->name = $name;
        $section->content = null;
        $this->sections[$name] = $section;
        $this->sectionStack->push($section);
        ob_start();
    }

    public function stop()
    {
        $content = ob_get_contents();
        ob_end_clean();
        $lastSection = $this->sectionStack->pop();
        $lastSection->content = $content;
    }

    public function render(string $viewName, array $params = []): string
    {
        $viewName = $this->view->resolvePath($viewName);
        $template = new Template($this, $viewName, $params);
        return $this->renderTemplate($template);
    }

    public function parent()
    {
        $sectionName = $this->sectionStack->top()->name;
        return $this->getSectionPlacholder($sectionName);
    }

    public function renderTemplate(Template $template)
    {
        $rootTemplate = null;

        while ($template) {
            $rootTemplate = $template;
            $this->processTemplate($template);
            $parentTemplate = $template->parent;

            if ($parentTemplate) {
                $parentTemplate->content = $template->content;
            }
            $template = $parentTemplate;
        }

        return $rootTemplate->content;
    }

    private function processTemplate(Template $template)
    {
        $this->currentTemplate = $template;
        $this->currentTemplate->render();
    }

    public function extend(string $viewName)
    {
        $viewName = $this->view->resolvePath($viewName);
        $template = new Template($this, $viewName, []);
        $this->currentTemplate->setParent($template);
    }
}
