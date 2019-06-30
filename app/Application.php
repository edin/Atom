<?php

namespace App;

class Application extends \Atom\Application
{
    public function registerRoutes()
    {
        $router = $this->getRouter();

        $group = $router->addGroup("/");
        $group->addRoute("GET", "/", "HomeController@index");
        $group->addRoute("GET", "/item", "HomeController@item");
        $group->addRoute("GET", "/users.json", "HomeController@json");

        $group->addRoute("GET", "/login", "AccountController@login");
        $group->addRoute("GET", "/logout", "AccountController@logout");

        $group = $router->addGroup("/api");
        $group->addRoute("GET", "/users", "HomeController@onGet");
        $group->addRoute("POST", "/users", "HomeController@onPost");
        $group->addRoute("PUT", "/users/{id}", "HomeController@onPut");
        $group->addRoute("PATCH", "/users", "HomeController@onPatch");
        $group->addRoute("DELETE", "/users", "HomeController@onDelete");
        $group->addRoute("OPTIONS", "/users", "HomeController@onOptions");
        $group->addRoute("HEAD", "/users", "HomeController@onHead");
    }

    public function registerServices()
    {
        $di = $this->getContainer();

        $di->bind(\Atom\View\View::class)->withName("View")->asShared()->toFactory(function () use($di) {
            $view = new \Atom\View\View($di);
            $view->setViewsDir(dirname(__FILE__) . "/Views");
            $view->setEngines([
                ".php" => "ViewEngine",
            ]);
            return $view;
        });

        $di->bind(\Atom\View\View::class)->withName("View")->asShared()->toFactory(function () use($di) {
            $view = new \Atom\View\View($di);
            $view->setViewsDir(dirname(__FILE__) . "/Views");
            $view->setEngines([
                ".php" => "ViewEngine",
            ]);
            return $view;
        });

        $di->UserRepository    = \App\Models\UserRepository::class;
        $di->HomeController    = \App\Controllers\HomeController::class;
        $di->AccountController = \App\Controllers\AccountController::class;
    }

    public function resolveController($name)
    {
        return $this->getContainer()->get($name);
    }
}