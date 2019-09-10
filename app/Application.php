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
        $router->addMiddleware(\App\Middlewares\LogMiddleware::class);

        $router->addGroup("/", function (RouteGroup $group) {
            $group->addMiddleware(\App\Middlewares\LogMiddleware::class);

            $group->addRoute("GET", "/", "HomeController@index")
                ->withName("home")
                ->addMiddleware(\App\Middlewares\LogMiddleware::class);

            $group->addRoute("GET", "/item", "HomeController@item");
            $group->addRoute("GET", "/json", "HomeController@json");
            $group->addRoute("GET", "/login", "AccountController@login");
            $group->addRoute("GET", "/logout", "AccountController@logout");

            $group->addGroup("/sub1", function (RouteGroup $group) {
                $group->addRoute("GET", "/", "HomeController@index");
                $group->addRoute("GET", "/item", "HomeController@item");
            });
        });

        $router->addGroup("/api", function (RouteGroup $group) {
            $group->addRoute("GET", "/users", "HomeController@onGet");
            $group->addRoute("POST", "/users", "HomeController@onPost");
            $group->addRoute("PUT", "/users/{id}", "HomeController@onPut");
            $group->addRoute("PATCH", "/users", "HomeController@onPatch");
            $group->addRoute("DELETE", "/users", "HomeController@onDelete");
            $group->addRoute("OPTIONS", "/users", "HomeController@onOptions");

            $group->addRoute("GET", "/hello", function (UserRepository $users) {
                return $users->findAll();
            });
        });
    }

    public function registerServices(Container $container)
    {
        $container->bind(\Atom\View\View::class)
        ->withName("View")
        ->asShared()
        ->toFactory(function () use ($container) {
            $view = new \Atom\View\View($container);
            $view->setViewsDir(dirname(__FILE__) . "/Views");
            $view->setEngines([
                ".php" => "ViewEngine",
            ]);
            return $view;
        });

        $container->UserRepository    = \App\Models\UserRepository::class;
        $container->HomeController    = \App\Controllers\HomeController::class;
        $container->AccountController = \App\Controllers\AccountController::class;
    }
}
