<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class Toast implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public ?Page $page;
    public ?ToastModel $model = null;
    public AttributeBag $attributes;
    public ?bool $show = null;
    public bool $flash = true;
    public string $title = "";
    public string $description = "";
    public string $text = "";
    public string $variant = "neutral";
    public string $position = "top-end";
    public string $role = "status";
    public string $class = "";

    public function render(): string
    {
        $this->bindModel();
        $this->bindFlash();

        if (!$this->shouldShow()) {
            return "";
        }

        return Html::tag("div", [
            "class" => "atom-toast-region",
            "data-position" => $this->position,
        ], Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-toast", $this->class),
            "data-variant" => $this->variant,
            "role" => $this->role,
        ], $this->attributes->all()), $this->body()));
    }

    private function body(): string
    {
        $content = Html::tag("div", ["class" => "atom-toast__content"], $this->contentHtml());

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-toast__actions"], $this->actions->render());
        }

        return $content;
    }

    private function contentHtml(): string
    {
        $content = "";

        if ($this->title !== "") {
            $content .= Html::tag("strong", ["class" => "atom-toast__title"], Html::escape($this->title));
        }

        if ($this->description !== "") {
            $content .= Html::tag("p", ["class" => "atom-toast__description"], Html::escape($this->description));
        }

        if ($this->content !== null || $this->text !== "") {
            $content .= Html::tag("div", ["class" => "atom-toast__body"], $this->content());
        }

        return $content;
    }

    private function content(): string
    {
        if ($this->content !== null) {
            return $this->content->renderOr(Html::escape($this->text));
        }

        return Html::escape($this->text);
    }

    private function bindFlash(): void
    {
        $page = $this->page();
        if (
            !$this->flash ||
            $page === null ||
            !$page->hasFlash() ||
            $this->hasExplicitContent()
        ) {
            return;
        }

        $this->title = $page->flashTitle();
        $this->description = $page->flashMessage();
        $this->variant = $page->flashVariant();
    }

    private function shouldShow(): bool
    {
        if ($this->show !== null) {
            return $this->show;
        }

        if ($this->hasExplicitContent()) {
            return true;
        }

        return $this->page()?->hasFlash() === true;
    }

    private function hasExplicitContent(): bool
    {
        return $this->title !== "" || $this->description !== "" || $this->text !== "" || $this->content !== null;
    }

    private function page(): ?Page
    {
        return isset($this->page) ? $this->page : null;
    }

    private function bindModel(): void
    {
        if ($this->model === null) {
            return;
        }

        if ($this->show === null) {
            $this->show = $this->model->show;
        }

        if ($this->hasExplicitContent()) {
            return;
        }

        $this->title = $this->model->title;
        $this->description = $this->model->description;
        $this->text = $this->model->text;
        $this->variant = $this->model->variant;
    }
}
