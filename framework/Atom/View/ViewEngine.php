<?php

namespace Atom\View;

final class ViewEngine implements \Atom\Interfaces\IViewEngine
{
    public $sections = [];
    public $prevSections = [];
    private $sectionStack;

    public function __construct() {
        $this->sectionStack = new \SplStack();
    }

    public function getSectionPlacholder(string $name): string {
        return "<!-- {SECTION:$name:SECTION} -->";
    }

    public function section($name) {

        $prevSection = $this->prevSections[$name] ?? null;
        $sectionContent = $this->sections[$name]->content ?? "";

        if ($prevSection) {
            $sectionContent = str_replace($this->getSectionPlacholder($name), $sectionContent, $prevSection->content);
        }

        return $sectionContent;
    }

    public function start($name) {

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

    public function stop() {
        $content = ob_get_contents();
        ob_end_clean();
        $lastSection = $this->sectionStack->pop();
        $lastSection->content = $content;
    }

    public function render(string $templatePath, array $params = []): string
    {
        return $this->renderTemplate(new Template($this, $templatePath));
    }


    public function parent() {
        return $this->getSectionPlacholder($this->sectionStack->top()->name);
    }

    public function renderTemplate(Template $template) {
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

    private function processTemplate(Template $template) {
        $this->currentTemplate = $template;
        $this->currentTemplate->render();
    }

    public function extend($template) {
        $this->currentTemplate->setParent(new Template($this, $template));
    }
}



// $view = new AtomViewEngine();
// $template = new Template($view, "view-template.php");
// $result = $view->renderTemplate($template);

// echo $result;