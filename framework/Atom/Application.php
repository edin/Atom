<?php

namespace Atom;

use Latte\Engine;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;


class Application {
    public static $app = null;

    public function __construct()
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri        = $_SERVER['REQUEST_URI'];

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
    }

    public function getDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/', "Hello");
        });
        return $dispatcher;
    }

    public function configure()
    {
    }

    public function getService($name)
    {
        $this->container->resolve($name);
    }

    public function dispatch()
    {
        $dispatcher = $this->getDispatcher();

        $server = new Server($_SERVER);
        $uri = $server->getUri();

        $routeInfo = $dispatcher->dispatch($server->getRequestMethod(), $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                echo "Nou Found";
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars    = $routeInfo[2];

                $result = $this->executeHandler($handler, $vars);
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

        $appDir = dirname(dirname(__DIR__));

        $latte = new \Latte\Engine;
        $latte->setTempDirectory($appDir . '/resource/cache');
        echo $latte->renderToString("app/Views/{$result->viewName}.latte", $result->model);
    }

    public function executeHandler()
    {
        $handler = new \App\Controllers\HomeController();
        $result = $handler->index();
        return $result;
    }

    public static function setApplication($instance)
    {
        Application::$app = $instance;
        return $instance;
    }

    public function run()
    {
        $this->dispatch();
    }
}