<?php

namespace App;

class Application extends \Atom\Application {
    public function configure() {

        $router = $this->getRouter();
        $group  = $router->addGroup();

        $group->addRoute("GET", "/", "\App\Controllers\HomeController@index")->withName("cool");
        $group->addRoute("GET", "/", "\App\Controllers\HomeController@index")->withName("cool");
        $group->addRoute("GET", "/", "\App\Controllers\HomeController@index")->withName("cool");

        return $router;
    }
}