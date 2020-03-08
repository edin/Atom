<?php

namespace App;

use App\Middlewares\LogMiddleware;
use App\Models\UserRepository;
use Atom\Container\Container;
use Atom\Router\RouteGroup;
use Atom\Router\Router;

class Routes
{
    public function configureServices(Container $container)
    {
        $container->UserRepository    = \App\Models\UserRepository::class;
        $container->HomeController    = \App\Controllers\HomeController::class;
        $container->AccountController = \App\Controllers\AccountController::class;
    }

    public function configure(Router $router)
    {
        $router->addGroup("/", function (RouteGroup $group) {
            $group->addMiddleware(LogMiddleware::class);
            $group->addRoute("GET", "", "HomeController@index")->withName("home");
            $group->addRoute("GET", "/item", "HomeController@item");
            $group->addRoute("GET", "json", "HomeController@json");
            $group->addRoute("GET", "login", "AccountController@login");
            $group->addRoute("GET", "logout", "AccountController@logout");
        });

        $router->addGroup("/api", function (RouteGroup $group) {
            $group->addRoute("GET", "users", "HomeController@onGet");
            $group->addRoute("POST", "users", "HomeController@onPost");
            $group->addRoute("PUT", "users/{id}", "HomeController@onPut");
            $group->addRoute("PATCH", "users", "HomeController@onPatch");
            $group->addRoute("DELETE", "users", "HomeController@onDelete");
            $group->addRoute("OPTIONS", "users", "HomeController@onOptions");

            $group->addRoute("GET", "hello", function (UserRepository $users) {
                return $users->findAll();
            });
        });
    }
}
