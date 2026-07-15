<?php

declare(strict_types=1);

namespace Atom\Modules\Framework\Components;

use Atom\Security\CsrfTokenManagerInterface;
use Atom\View\Component\AttributeBag;
use Atom\View\Component\ComponentInterface;
use Atom\View\Component\Fragment;
use Atom\View\Html;
use RuntimeException;

final class Form implements ComponentInterface
{
    public ?Fragment $content = null;
    public AttributeBag $attributes;
    public string $method = "post";
    public string $action = "";
    public string $submit = "";
    public string $class = "";
    public bool $csrf = false;
    public mixed $model = null;

    public function __construct(private ?CsrfTokenManagerInterface $csrfTokens = null)
    {
    }

    public function render(): string
    {
        return Html::tag("form", Html::mergeAttributes([
            "method" => $this->method,
            "action" => $this->action,
            "atom:submit" => $this->submit,
            "class" => Html::classes("atom-form", $this->class),
        ], $this->attributes->all()), $this->csrfField() . ($this->content?->render($this->contentVariables()) ?? ""));
    }

    /**
     * @return array<string, mixed>
     */
    private function contentVariables(): array
    {
        return $this->model === null ? [] : ["model" => $this->model];
    }

    private function csrfField(): string
    {
        if (!$this->csrf || strtoupper($this->method) === "GET") {
            return "";
        }

        if ($this->csrfTokens === null) {
            throw new RuntimeException("CSRF-enabled forms require CsrfTokenManagerInterface.");
        }

        return Html::voidTag("input", [
            "type" => "hidden",
            "name" => CsrfTokenManagerInterface::FIELD_NAME,
            "value" => $this->csrfTokens->token(),
        ]);
    }
}
