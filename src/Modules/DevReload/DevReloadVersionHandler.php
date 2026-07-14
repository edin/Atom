<?php

declare(strict_types=1);

namespace Atom\Modules\DevReload;

use Atom\Http\Response;
use Atom\Support\Paths;

final readonly class DevReloadVersionHandler
{
    public function handle(Response $response, Paths $paths, DevReloadOptions $options): Response
    {
        $version = (new DevReloadWatcher($paths))->version($options->watchPaths);

        return $response
            ->header("Cache-Control", "no-store")
            ->json(["version" => $version]);
    }
}
