<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Page\Page;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class SnackBar implements ComponentInterface
{
    public ?Fragment $content = null;
    public ?Fragment $actions = null;
    public ?Page $page;
    public AttributeBag $attributes;
    public ?bool $show = null;
    public bool $flash = true;
    public string $text = "";
    public string $variant = "neutral";
    public string $position = "bottom-center";
    public string $role = "status";
    public string $class = "";

    public function render(): string
    {
        $this->bindFlash();

        if (!$this->shouldShow()) {
            return "";
        }

        return Html::tag("div", [
            "class" => "atom-snackbar-region",
            "data-position" => $this->position,
        ], Html::tag("div", Html::mergeAttributes([
            "class" => Html::classes("atom-snackbar", $this->class),
            "data-variant" => $this->variant,
            "role" => $this->role,
        ], $this->attributes->all()), $this->body()));
    }

    private function body(): string
    {
        $content = Html::tag("div", ["class" => "atom-snackbar__message"], $this->message());

        if ($this->actions !== null) {
            $content .= Html::tag("div", ["class" => "atom-snackbar__actions"], $this->actions->render());
        }

        return $content;
    }

    private function message(): string
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

        $this->text = $page->flashMessage() !== "" ? $page->flashMessage() : $page->flashTitle();
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
        return $this->text !== "" || $this->content !== null;
    }

    private function page(): ?Page
    {
        return isset($this->page) ? $this->page : null;
    }
}
