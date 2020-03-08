<?php

namespace App;

use Atom\Container\Container;

class Configuration
{
    public $viewsDirectory = __DIR__ . "/Views";

    public function configureServices(Container $container)
    {
        $container->bind(Configuration::class)->toInstance($this)->withName("Configuration");
    }
}
