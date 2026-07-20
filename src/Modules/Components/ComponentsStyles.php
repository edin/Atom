<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class ComponentsStyles implements ComponentInterface
{
    public string $resourcePath = Components::DEFAULT_RESOURCE_PATH;
    public string $version = Components::STYLES_VERSION;

    public function render(): string
    {
        $url = rtrim($this->resourcePath, "/") . "/atom.css";
        if ($this->version !== "") {
            $url .= "?v=" . rawurlencode($this->version);
        }

        return Html::voidTag("link", [
            "rel" => "stylesheet",
            "href" => $url,
        ]);
    }
}
