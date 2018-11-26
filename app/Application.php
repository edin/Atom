<?php

namespace App;

use Opis\Database\Database;
use Opis\Database\Connection;

class Application extends \Atom\Application {

    public function registerRoutes() {
        $router = $this->getRouter();

        $group  = $router->addGroup("/");
        $group->addRoute("GET" , "/", "HomeController@index");
        $group->addRoute("GET" , "/item", "HomeController@item");

    }

    public function registerServices()
    {
        $di = $this->getContainer();

        $di->Database = function () {
            $connection = new Connection('mysql:host=localhost;dbname=chatbot', 'root', 'root');
            $db         = new Database($connection);
            return $db;
        };

        $di->UserRepository = function () {

        };
    }

    public function resolveController($name) {

    }
}