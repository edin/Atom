<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Html;
use Atom\View\Component\ComponentInterface;

final class ValidationSummary implements ComponentInterface
{
    public Page $page;
    public string $class = "validation-summary";

    public function render(): string
    {
        $errors = $this->page->errors()->all();
        if ($errors === []) {
            return "";
        }

        $html = '<div class="' . Html::escape($this->class) . '"><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . Html::escape($error->message) . '</li>';
        }

        return $html . '</ul></div>';
    }
}
