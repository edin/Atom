<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Component\ComponentInterface;

final class FieldError implements ComponentInterface
{
    public Page $page;
    public string $name;
    public string $class = "field-error";

    public function render(): string
    {
        $message = $this->page->errors()->first($this->name);
        if ($message === null) {
            return "";
        }

        return '<p class="' . $this->escape($this->class) . '">' . $this->escape($message) . '</p>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }
}
