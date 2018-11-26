<?php

namespace Atom;

use Atom\Router\Router;
use Atom\Container\Container;

use Latte\Engine;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;


class Application
{
    public static $app = null;

    private $router;
    private $container;

    public function __construct()
    {
        self::$app = $this;
    }

    final public function getRouter(): Router
    {
        if (!isset($this->router)) {
            $this->router = new Router();
        }
        return $this->router;
    }

    final public function getContainer(): Container
    {
        if (!isset($this->container)) {
            $this->container = new Container();
        }
        return $this->container;
    }

    final public function getDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            $routes = $this->getRouter()->getAllRoutes();

            foreach($routes as $route) {
                $r->addRoute($route->method, $route->getFullPath(), $route);
            }

        });
        return $dispatcher;
    }

    public function registerRoutes() { }
    public function registerServices() { }

    public function dispatch()
    {
        $dispatcher = $this->getDispatcher();

        $server = new Server($_SERVER);
        $uri = $server->getUri();

        $routeInfo = $dispatcher->dispatch($server->getRequestMethod(), $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                echo "Not Found";
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                break;
            case \FastRoute\Dispatcher::FOUND:
                $route = $routeInfo[1];
                $vars  = $routeInfo[2];

                $result = $this->executeHandler($route, $vars);
                $this->processResult($result);
                break;
        }
    }

    public function processResult($result)
    {
        if (is_string($result)) {
            echo $result;
            return;
        }

        $appDir   = dirname(dirname(__DIR__));
        $cacheDir = $appDir . '/resource/cache';

        $latte = new \Latte\Engine;
        $latte->setTempDirectory($cacheDir);
        echo $latte->renderToString( $appDir ."/app/Views/{$result->viewName}.latte", $result->model);
    }

    public function executeHandler($route, $vars)
    {
        $parts = \explode("@", $route->handler);
        $controller = $parts[0] ?? "";
        $action     = $parts[1] ?? "index";

        $controller = new \App\Controllers\HomeController();
        $result = $controller->{$action}();
        return $result;
    }

    public function initialize()
    {
        $this->registerServices();
        $this->registerRoutes();
    }

    public function run()
    {
        $this->initialize();
        $this->dispatch();
    }
}