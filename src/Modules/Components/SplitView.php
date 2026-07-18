<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class SplitView implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $side = null;
    public ?SidePanelModel $model = null;
    public AttributeBag $attributes;
    public ?bool $showSide = null;
    public string $sideWidth = "380px";
    public string $gap = "md";
    public string $class = "";

    public function render(): string
    {
        $this->bindModel();
        $showSide = $this->shouldShowSide();
        $content = Html::tag("div", ["class" => "atom-split-view__main"], $this->content?->render() ?? "");

        if ($showSide && $this->side !== null) {
            $content .= Html::tag("aside", ["class" => "atom-split-view__side"], $this->side->render());
        }

        $attributes = $this->attributes->all();
        unset($attributes["style"]);

        return Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-split-view", ["has-side" => $showSide && $this->side !== null], $this->class),
            "data-gap" => $this->gap,
            "style" => $this->style(),
        ], $attributes), $content);
    }

    private function style(): string
    {
        $style = "--atom-split-side-width: {$this->sideWidth};";
        $custom = $this->attributes->get("style");

        return is_string($custom) && trim($custom) !== "" ? $style . " " . $custom : $style;
    }

    private function bindModel(): void
    {
        if ($this->model !== null && $this->showSide === null) {
            $this->showSide = $this->model->show;
        }
    }

    private function shouldShowSide(): bool
    {
        return $this->showSide ?? true;
    }
}
