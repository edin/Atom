<?php

namespace Atom;

use Latte\Engine;

final class View
{
    public function render() {
        $appDir   = dirname(dirname(__DIR__));
        $cacheDir = $appDir . '/resource/cache';

        $latte = new \Latte\Engine;
        $latte->setTempDirectory($cacheDir);



        echo $latte->renderToString( $appDir ."/app/Views/{$result->viewName}.latte", $result->model);
    }
}