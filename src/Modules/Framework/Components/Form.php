<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Form implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $method = "post";
    public string $action = "";
    public string $submit = "";
    public string $class = "";
    public mixed $model = null;

    public function render(): string
    {
        return Html::tag("form", Html::mergeAttributes([
            "method" => $this->method,
            "action" => $this->action,
            "atom:submit" => $this->submit,
            "class" => Html::classes("atom-form", $this->class),
        ], $this->attributes->all()), $this->content?->render($this->contentVariables()) ?? "");
    }

    /**
     * @return array<string, mixed>
     */
    private function contentVariables(): array
    {
        return $this->model === null ? [] : ["model" => $this->model];
    }
}
