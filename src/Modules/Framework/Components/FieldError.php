<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Html;
use Atom\View\Component\ComponentInterface;

final class FieldError implements ComponentInterface
{
    public Page $page;
    public string $name;
    public ?string $id = null;
    public string $class = "field-error";

    public function render(): string
    {
        $message = $this->page->errors()->first($this->name);
        if ($message === null) {
            return "";
        }

        $id = $this->id ?? str_replace([".", "[", "]"], "-", $this->name) . "-error";

        return Html::tag("p", [
            "id" => $id,
            "class" => $this->class,
        ], Html::escape($message));
    }
}
