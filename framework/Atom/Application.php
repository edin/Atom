<?php

namespace Atom;

use Atom\Router\Router;
use Atom\Container\Container;

use Latte\Engine;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

abstract class Application
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

    final public function getDispatcher() /* DispatcherInterface */
    {
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            $routes = $this->getRouter()->getAllRoutes();
            foreach($routes as $route) {
                $r->addRoute($route->method, $route->getFullPath(), $route);
            }
        });
        return $dispatcher;
    }

    public function registerRoutes()   { }
    public function registerServices() { }

    public function dispatch()
    {
        $dispatcher = $this->getDispatcher();

        // TODO: Replace with PSR-thing
        $server = new Server($_SERVER);
        $uri = $server->getUri();

        $routeInfo = $dispatcher->dispatch($server->getRequestMethod(), $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                return $this->routeNotFound($uri);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return $this->methodNotAllowed();
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

        //$this->getResponseProcessor($result);
    }

    public function executeHandler($route, $vars)
    {
        $parts = \explode("@", $route->handler);
        $controller = $parts[0] ?? "";
        $methodName = $parts[1] ?? "index";

        $controller = $this->resolveController($controller);

        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod($methodName);

        if ($method == null) {
            throw new \Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
        }

        $container = $this->getContainer();

        $parameters = [];
        foreach($method->getParameters() as $param) {

            $paramName = $param->getName();
            $paramPos  = $param->getPosition();
            $parameters[$paramPos] = null;

            if ($param->isDefaultValueAvailable()) {
                $parameters[$paramPos] = $param->getDefaultValue();
            } else {
                $parameters[$paramPos] = null;
            }

            if (isset($vars[$paramPos])) {
                $parameters[$paramPos] = $vars[$paramName];
            }

            if ($param->hasType()) {
                $reflectedTypeClass = new \ReflectionClass($param->getType()->getName());
                $fullName = $reflectedTypeClass->getName();
                $shortName = $reflectedTypeClass->getShortName();

                if ($container->has($fullName))
                {
                    $parameters[$paramPos] = $container->get($fullName);
                }
                elseif ($container->has($shortName))
                {
                    $parameters[$paramPos] = $container->get($shortName);
                }
            }
       };

        $result =  call_user_func_array([$controller, $methodName], $parameters);
        return $result;
    }

    public function routeNotFound() {
        return "Not Found";
    }

    public function resolveController($name) {
        return $this->getContainer()->get($name);
    }

    public function initialize()
    {
        $this->registerServices();
        $this->registerRoutes();
    }

    public function run()
    {
        $this->initialize();
        $response = $this->dispatch();

        echo $response;
    }
}