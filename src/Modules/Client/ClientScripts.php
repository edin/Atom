<?php

declare(strict_types=1);

namespace Atom\Modules\Client;

use Atom\View\Component\ComponentInterface;
use Atom\View\Html;

final class ClientScripts implements ComponentInterface
{
    public string $resourcePath = Client::DEFAULT_RESOURCE_PATH;
    public string $atomVersion = Client::ATOM_VERSION;
    public string $morphdomVersion = Client::MORPHDOM_VERSION;
    public string $adapterVersion = Client::MORPHDOM_ADAPTER_VERSION;
    public bool $morphdom = false;

    public function render(): string
    {
        $scripts = [
            Html::tag("script", ["src" => $this->asset("atom.js", $this->atomVersion)]),
        ];

        if ($this->morphdom) {
            $scripts[] = Html::tag("script", ["src" => $this->asset("morphdom.js", $this->morphdomVersion)]);
            $scripts[] = Html::tag("script", ["src" => $this->asset("atom-morphdom.js", $this->adapterVersion)]);
        }

        return implode("\n", $scripts);
    }

    private function asset(string $file, string $version): string
    {
        $url = rtrim($this->resourcePath, "/") . "/" . $file;

        return $version === "" ? $url : $url . "?v=" . rawurlencode($version);
    }
}
