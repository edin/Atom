<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class Progress implements ComponentInterface
{
    public AttributeBag $attributes;
    public ?float $value = null;
    public float $max = 100;
    public string $label = "";
    public bool $showValue = false;
    public string $variant = "primary";
    public string $size = "md";
    public string $class = "";

    public function render(): string
    {
        $max = $this->max > 0 ? $this->max : 100;
        $value = $this->value === null ? null : max(0, min($this->value, $max));
        $header = $this->header($value, $max);
        $progress = Html::tag("progress", Html::mergeAttributes([
            "class" => Html::classes("atom-progress", $this->class),
            "value" => $value,
            "max" => $max,
            "data-variant" => $this->variant,
            "data-size" => $this->size,
            "aria-label" => $this->label === "" ? "Progress" : $this->label,
        ], $this->attributes->all()), "");

        return Html::tag("div", ["class" => "atom-progress-field"], $header . $progress);
    }

    private function header(?float $value, float $max): string
    {
        if ($this->label === "" && !$this->showValue) {
            return "";
        }

        $content = $this->label === ""
            ? ""
            : Html::tag("span", ["class" => "atom-progress__label"], Html::escape($this->label));

        if ($this->showValue && $value !== null) {
            $percentage = (int) round(($value / $max) * 100);
            $content .= Html::tag("span", ["class" => "atom-progress__value"], $percentage . "%");
        }

        return Html::tag("div", ["class" => "atom-progress__header"], $content);
    }
}
