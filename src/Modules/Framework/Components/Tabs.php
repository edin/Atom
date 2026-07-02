<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\Children;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Tabs implements ComponentInterface
{
    /** @var Tab[] */
    #[Children("Tab", Tab::class)]
    public array $tabs = [];
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $active = "";
    public string $label = "Tabs";
    public string $class = "";

    public function render(): string
    {
        $active = $this->activeTab();
        $headers = "";
        foreach ($this->tabs as $tab) {
            $headers .= $tab->renderHeader($tab === $active);
        }

        $nav = Html::tag("nav", Html::mergeAttributes([
            "class" => Html::classes("atom-tabs", $this->class),
            "aria-label" => $this->label,
        ], $this->attributes->all()), $headers);

        $panel = $active === null ? "" : Html::tag(
            "div",
            ["class" => "atom-tabs__panel"],
            $active->renderContent()
        );

        return Html::tag("div", ["class" => "atom-tabs-frame"], $nav . $panel . ($this->content?->render() ?? ""));
    }

    private function activeTab(): ?Tab
    {
        foreach ($this->tabs as $tab) {
            if ($tab->name !== "" && $tab->name === $this->active) {
                return $tab;
            }
        }

        return $this->tabs[0] ?? null;
    }
}
