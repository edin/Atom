<?php

require '../vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

(new \App\Application())->run();

function dd($data)
{
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
    exit();
}
