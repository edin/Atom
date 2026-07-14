<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Page\FormModel;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/forms", name: "showcase.components.forms")]
final class FormsPage extends AppPage
{
    public string $title = "Forms - Atom Showcase";

    public string $pageTitle = "Standalone page property";

    public string $summary = "A standalone textarea field.";

    public bool $published = true;

    /** @var list<object{id: int, name: string}> */
    public array $categories = [];

    #[State]
    #[FormModel]
    public FormsDemoModel $form;

    public function __construct()
    {
        $this->form = new FormsDemoModel();
    }

    public function get(): void
    {
        $this->loadCategories();
    }

    #[PageAction("submit")]
    public function submit(): void
    {
        if ($this->validateModel($this->form)) {
            $this->flash("Your form model was hydrated, validated, and saved by a page action.", "success", "Form submitted");
        }

        $this->loadCategories();
    }

    private function loadCategories(): void
    {
        $this->categories = [
            (object) ["id" => 1, "name" => "Documentation"],
            (object) ["id" => 2, "name" => "Components"],
            (object) ["id" => 3, "name" => "Examples"],
        ];
    }
}
