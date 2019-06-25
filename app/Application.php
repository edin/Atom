<?php

namespace App;

use Atom\Router\Router;
use Atom\Router\RouteGroup;
use Atom\Container\Container;
use App\Models\UserRepository;

class Application extends \Atom\Application
{
    public function registerRoutes(Router $router)
    {
        $router->addGroup("/", function(RouteGroup $group) {

            $group->addMiddleware(\App\Middlewares\LogMiddleware::class);

            $group->addRoute("GET", "/", "HomeController@index")->withName("home");
            $group->addRoute("GET", "/item", "HomeController@item");
            $group->addRoute("GET", "/users-json", "HomeController@json");
            $group->addRoute("GET", "/login", "AccountController@login");
            $group->addRoute("GET", "/logout", "AccountController@logout");
        });

        $router->addGroup("/api", function(RouteGroup $group) {

            $group->addRoute("GET", "/users", "HomeController@onGet");
            $group->addRoute("POST", "/users", "HomeController@onPost");
            $group->addRoute("PUT", "/users/{id}", "HomeController@onPut");
            $group->addRoute("PATCH", "/users", "HomeController@onPatch");
            $group->addRoute("DELETE", "/users", "HomeController@onDelete");
            $group->addRoute("OPTIONS", "/users", "HomeController@onOptions");

            $group->addRoute("GET", "/hello", function(UserRepository $users) {
                return $users->findAll();
            });
        });
    }

    public function registerServices(Container $container)
    {
        $container->View = function ($di) {
            $view = new \Atom\View\View($di);
            $view->setViewsDir(dirname(__FILE__) . "/Views");
            $view->setEngines([
                ".php" => "ViewEngine",
            ]);
            return $view;
        };

        $container->UserRepository    = function ($di) {
             return new \App\Models\UserRepository();
        };

        $container->HomeController    = \App\Controllers\HomeController::class;
        $container->AccountController = \App\Controllers\AccountController::class;
    }
}