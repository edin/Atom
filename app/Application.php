<?php

namespace App;

use Opis\Database\Database;
use Opis\Database\Connection;

class Application extends \Atom\Application {

    public function registerRoutes() {
        $router = $this->getRouter();

        $group  = $router->addGroup("/");
        $group->addRoute("GET" , "/",           "HomeController@index");
        $group->addRoute("GET" , "/item",       "HomeController@item");
        $group->addRoute("GET" , "/users.json", "HomeController@json");

        $group->addRoute("GET" , "/function/{id}", function($id, \App\Models\UserRepository $repository) {
            return [
                "result" => "From Function",
                "id" => $id,
                "users" => $repository->findAll()
            ];
        });

        $group->addRoute("GET" , "/login",  "AccountController@login");
        $group->addRoute("GET" , "/logout", "AccountController@logout");

        $group  = $router->addGroup("/api");
        $group->addRoute("GET"    , "/users",      "HomeController@onGet");
        $group->addRoute("POST"   , "/users",      "HomeController@onPost");
        $group->addRoute("PUT"    , "/users/{id}", "HomeController@onPut");
        $group->addRoute("PATCH"  , "/users",      "HomeController@onPatch");
        $group->addRoute("DELETE" , "/users",      "HomeController@onDelete");
        $group->addRoute("OPTIONS", "/users",      "HomeController@onOptions");
        $group->addRoute("HEAD"   , "/users",      "HomeController@onHead");
    }

    public function registerServices()
    {
        $di = $this->getContainer();

        $di->Database = function () {
            $connection = new Connection('mysql:host=localhost;dbname=atom', 'root', 'root');
            return new Database($connection);
        };

        $di->LatteViewEngine = function($di) {
            $engine = new \Atom\View\LatteViewEngine($di->View);
            $engine->cachePath = dirname(__DIR__) . '/resource/cache';
            return $engine;
        };

        $di->PhpViewEngine = function($di) {
            $engine = new \Atom\View\PhpViewEngine($di->View);
            return $engine;
        };

        $di->View = function ($di) {
            $view = new \Atom\View\View($di);
            $view->setDefaultExt(".latte");  // Could avoid this by using first engine as default
            $view->setViewDir(dirname(__FILE__) . "/Views");
            $view->setEngines([
                "latte" => "LatteViewEngine",
                "php"   => "PhpViewEngine"
            ]);
            return $view;
        };

        $di->UserRepository    = function ($di) { return new \App\Models\UserRepository($di->Database); };
        $di->HomeController    = function ($di) { return new \App\Controllers\HomeController();         };
        $di->AccountController = function ($di) { return new \App\Controllers\AccountController();      };
    }

    public function resolveController($name) {
        return $this->getContainer()->get($name);
    }
}