<?php

declare(strict_types=1);

namespace Atom\Mail;

use Throwable;

final readonly class MailTemplateRenderer
{
    public function render(MailTemplate $template): string
    {
        if (!is_file($template->path)) {
            throw new MailException("Mail template '{$template->path}' does not exist.");
        }

        $bufferLevel = ob_get_level();

        try {
            ob_start();
            (static function (string $__path, array $__variables): void {
                extract($__variables, EXTR_SKIP);
                include $__path;
            })($template->path, $template->variables);

            return ob_get_clean() ?: "";
        } catch (Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw new MailException("Failed to render mail template '{$template->path}'.", previous: $exception);
        }
    }
}
