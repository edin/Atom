<?php

declare(strict_types=1);

namespace App\Components;

use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;

final class ConfirmDialog implements ComponentInterface
{
    public bool $show = false;
    public string $title = "Confirm";
    public ?Fragment $content = null;
    public ?Fragment $actions = null;

    public function render(): string
    {
        if (!$this->show) {
            return "";
        }

        $title = Html::escape($this->title);
        $content = $this->content?->render() ?? "";
        $actions = $this->actions?->render() ?? "";

        return <<<HTML
            <div class="dialog-backdrop" role="presentation">
                <section class="dialog" role="dialog" aria-modal="true">
                    <h2>{$title}</h2>
                    <div class="dialog-content">{$content}</div>
                    <div class="dialog-actions">{$actions}</div>
                </section>
            </div>
            HTML;
    }
}
