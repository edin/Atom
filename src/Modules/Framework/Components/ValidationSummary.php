<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Html;
use Atom\View\Component\ComponentInterface;

final class ValidationSummary implements ComponentInterface
{
    public Page $page;
    public string $class = "atom-validation-summary";

    public function render(): string
    {
        $errors = $this->page->errors()->all();
        if ($errors === []) {
            return "";
        }

        $items = "";
        foreach ($errors as $error) {
            $items .= Html::tag("li", content: Html::escape($error->message));
        }

        return Html::tag("div", ["class" => $this->class], Html::tag("ul", content: $items));
    }
}
