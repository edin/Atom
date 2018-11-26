<?php

namespace App;

use Opis\Database\Database;
use Opis\Database\Connection;

class Application extends \Atom\Application {

    public function registerRoutes() {
        $router = $this->getRouter();

        $group  = $router->addGroup("/");
        $group->addRoute("GET" , "/",     "HomeController@index");
        $group->addRoute("GET" , "/item", "HomeController@item");

        $group->addRoute("GET" , "/login", "AccountController@login");
        $group->addRoute("GET" , "/logout", "AccountController@logout");


        $group  = $router->addGroup("/api");
        $group->addRoute("GET"  , "/users", "HomeController@onGet");
        $group->addRoute("POST" , "/users", "HomeController@onPost");
        $group->addRoute("PUT"  , "/users", "HomeController@onPut");

    }

    public function registerServices()
    {
        $di = $this->getContainer();

        $di->Database = function () {
            $connection = new Connection('mysql:host=localhost;dbname=chatbot', 'root', 'root');
            $db         = new Database($connection);
            return $db;
        };

        $di->UserRepository    = function ($di) { };
        $di->HomeController    = function ($di) { return new \App\Controllers\HomeController();    };
        $di->AccountController = function ($di) { return new \App\Controllers\AccountController(); };

        // $di->namespaceOf("App\\Controllers\\", function ($di, $className) {
        // });
        // $di->instanceOf("App\\Models\\Entity", function ($di, $className) {
        // });
    }

    public function resolveController($name) {
        return $this->getContainer()->get($name);
    }
}