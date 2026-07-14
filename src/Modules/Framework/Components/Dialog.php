<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Dialog implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public ?DialogModel $model = null;
    public AttributeBag $attributes;
    public ?bool $show = null;
    public string $title = "";
    public string $description = "";
    public string $class = "";
    public string $label = "";
    public string $size = "";
    public bool $closable = false;
    public string $closeAction = "closeDialog";
    public string $closeLabel = "Close dialog";

    public function render(): string
    {
        $this->bindModel();

        if (!$this->shouldShow()) {
            return "";
        }

        $labelId = $this->labelId();
        $descriptionId = $this->descriptionId();
        $content = Html::tag("section", Html::mergeAttributes([
            "class" => Html::classes("atom-dialog", $this->class),
            "role" => "dialog",
            "aria-modal" => "true",
            "aria-labelledby" => $this->title === "" ? null : $labelId,
            "aria-describedby" => $this->description === "" ? null : $descriptionId,
            "aria-label" => $this->title === "" ? $this->label : null,
            "data-size" => $this->size,
        ], $this->attributes->all()), $this->body($labelId, $descriptionId));

        return Html::tag("div", ["class" => "atom-dialog-backdrop"], $content);
    }

    private function body(string $labelId, string $descriptionId): string
    {
        $content = "";

        if ($this->title !== "" || $this->description !== "" || $this->closable) {
            $content .= Html::tag("header", ["class" => "atom-dialog__header"], $this->header($labelId, $descriptionId));
        }

        $content .= Html::tag("div", ["class" => "atom-dialog__body"], $this->content?->render() ?? "");

        if ($this->actions !== null) {
            $content .= Html::tag("footer", ["class" => "atom-dialog__actions"], $this->actions->render());
        }

        return $content;
    }

    private function header(string $labelId, string $descriptionId): string
    {
        $content = "";

        $text = "";

        if ($this->title !== "") {
            $text .= Html::tag("h2", ["id" => $labelId, "class" => "atom-dialog__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $text .= Html::tag("p", ["id" => $descriptionId, "class" => "atom-dialog__description"], Html::escape($this->description));
        }

        if ($text !== "") {
            $content .= Html::tag("div", ["class" => "atom-dialog__heading"], $text);
        }

        if ($this->closable) {
            $content .= Html::tag("button", [
                "type" => "button",
                "class" => "atom-dialog__close",
                "aria-label" => $this->closeLabel,
                "atom:action" => $this->closeAction,
            ], "&times;");
        }

        return $content;
    }

    private function labelId(): string
    {
        return "dialog-" . substr(md5($this->title . "|" . $this->description), 0, 10);
    }

    private function descriptionId(): string
    {
        return $this->labelId() . "-description";
    }

    private function bindModel(): void
    {
        if ($this->model !== null && $this->show === null) {
            $this->show = $this->model->show;
        }
    }

    private function shouldShow(): bool
    {
        return $this->show === true;
    }
}
