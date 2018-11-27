<?php

namespace Atom;

use Atom\Router\Router;
use Atom\Container\Container;
use Latte\Engine;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;


abstract class Application
{
    public static $app = null;
    private $container = null;

    public function __construct($configuration = [])
    {
        self::$app = $this;
    }

    final public function getContainer(): Container
    {
        if ($this->container == null) {
            $this->container = new Container();
        }
        return $this->container;
    }

    final public function getRouter(): Router
    {
        return $this->getContainer()->Router;
    }

    final public function getDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            $routes = $this->getRouter()->getAllRoutes();
            foreach ($routes as $route) {
                $r->addRoute($route->method, $route->getFullPath(), $route);
            }
        });
        return $dispatcher;
    }

    public function registerDefaultServices()
    {
        $di = $this->getContainer();
        $di->Router = function () {
            return new Router();
        };
        $di->Application = function () {
            return $this;
        };

        $di->Request = function () {
            //return $this->getRequest();
        };

        $di->Response = function () {
            //return $this->getResponse();
        };
    }

    public function registerRoutes()
    {
    }

    public function registerServices()
    {
    }

    public function dispatch()
    {
        $dispatcher = $this->getDispatcher();

        $server = new Server($_SERVER);
        $uri = $server->getUri();
        $method = $server->getRequestMethod();

        $routeInfo = $dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                //return $this->routeNotFound($uri);
                throw new \Exception("Route $uri was not found.");

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $allowedMethodsStr = implode(", ", $allowedMethods);
                throw new \Exception("Method $method is not allowed. Allowed methods are $allowedMethodsStr.");
            case \FastRoute\Dispatcher::FOUND:
                $route = $routeInfo[1];
                $vars  = $routeInfo[2];
                $result = $this->executeHandler($route, $vars);
                return $this->processResult($result);
        }
    }


    public function processResult($result): ResponseInterface
    {
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($result instanceof \Atom\Interfaces\IViewInfo) {
            $view = $this->getContainer()->View;
            $content = $view->render($result);

            $factory = new Psr17Factory();
            $response = $factory->createResponse();
            $response->getBody()->write($content);
            return $response;
        }

        if (is_string($result)) {
            $factory = new Psr17Factory();
            $response = $factory->createResponse();
            $response->getBody()->write($result);
            return $response;
        }

        if (is_array($result) || is_object($result)) {
            $factory = new Psr17Factory();
            $response = $factory->createResponse()->withAddedHeader("Content-Type", "application/json");
            $response->getBody()->write(json_encode($result));
            return $response;
        }

        $factory = new Psr17Factory();
        $response = $factory->createResponse();
        $response->getBody()->write((string)$result);
        return $response;
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
        foreach ($method->getParameters() as $param) {
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

                if ($container->has($fullName)) {
                    $parameters[$paramPos] = $container->get($fullName);
                } elseif ($container->has($shortName)) {
                    $parameters[$paramPos] = $container->get($shortName);
                }
            }
        };

        $result =  call_user_func_array([$controller, $methodName], $parameters);
        return $result;
    }

    public function routeNotFound()
    {
        return "Not Found";
    }

    public function methodNotAllowed()
    {
        return "Method Not Allowed";
    }

    public function resolveController($name)
    {
        return $this->getContainer()->get($name);
    }

    public function initialize()
    {
        $this->registerDefaultServices();
        $this->registerServices();
        $this->registerRoutes();
    }

    public function run()
    {
        $this->initialize();
        $response = $this->dispatch();
        $this->sendResponse($response);
    }

    function sendResponse(ResponseInterface $response)
    {
        $http_line = sprintf('HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        header($http_line, true, $response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }
        $stream = $response->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        while (!$stream->eof()) {
            echo $stream->read(1024 * 8);
        }
    }
}