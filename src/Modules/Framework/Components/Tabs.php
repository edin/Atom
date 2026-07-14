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
    public ?TabsModel $model = null;
    public AttributeBag $attributes;
    public string $active = "";
    public string $action = "";
    public string $label = "Tabs";
    public string $class = "";

    public function render(): string
    {
        $this->bindModel();
        $this->bindTabActions();
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

    private function bindModel(): void
    {
        if ($this->model !== null && $this->active === "") {
            $this->active = $this->model->active;
        }
    }

    private function bindTabActions(): void
    {
        if ($this->action === "") {
            return;
        }

        foreach ($this->tabs as $tab) {
            if ($tab->action !== "" || $tab->href !== "") {
                continue;
            }

            $tab->action = str_replace("{name}", $tab->name, $this->action);
        }
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
