<?php

namespace Atom;

use Latte\Engine;
use Atom\Router\Router;
use FastRoute\Dispatcher;
use Nyholm\Psr7\Response;
use Atom\Container\Container;
use FastRoute\RouteCollector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function FastRoute\simpleDispatcher;

abstract class Application
{
    public static $app = null;
    private $container = null;

    public function __construct($configuration = [])
    {
        self::$app = $this;
    }

    public final function getContainer(): Container
    {
        if ($this->container == null) {
            $this->container = new Container();
        }
        return $this->container;
    }

    public final function getRouter(): Router
    {
        return $this->getContainer()->Router;
    }

    public final function getRequest(): RequestInterface
    {
        return $this->getContainer()->Request;
    }

    public final function getResponse(): ResponseInterface
    {
        return $this->getContainer()->Response;
    }

    public final function getDispatcher()
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
            $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $creator = new \Nyholm\Psr7Server\ServerRequestCreator($factory, $factory, $factory, $factory);
            $serverRequest = $creator->fromGlobals();
            return $serverRequest;
        };

        $di->Response = function () {
            $factory = new Psr17Factory();
            return $factory->createResponse();
        };
    }

    public function registerRoutes() { }
    public function registerServices() { }

    public function dispatch()
    {
        $request    = $this->getRequest();
        $dispatcher = $this->getDispatcher();

        $server = new Server($_SERVER);
        $uri = $server->getUri();
        $method = $server->getRequestMethod();

        $routeInfo = $dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
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

            $response = $this->getResponse();
            $response->getBody()->write($content);
            return $response;
        }

        if (is_string($result)) {
            $response = $this->getResponse();
            $response->getBody()->write($result);
            return $response;
        }

        if (is_array($result) || is_object($result)) {
            $response = $response = $this->getResponse()->withAddedHeader("Content-Type", "application/json");
            $response->getBody()->write(json_encode($result));
            return $response;
        }

        $response = $this->getResponse();
        $response->getBody()->write((string)$result);
        return $response;
    }

    public function executeHandler($route, $routeParams)
    {
        if ($route->handler instanceof \Closure) {

            $method = new \ReflectionFunction($route->handler);
            $parameters = $this->resolveMethodParameters($method, $routeParams);

            return call_user_func_array($route->handler, $parameters);
        }

        $parts = \explode("@", $route->handler);
        $controller = $parts[0] ?? "";
        $methodName = $parts[1] ?? "index";

        $controller = $this->resolveController($controller);

        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod($methodName);

        if ($method == null) {
            throw new \Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
        }

        $parameters = $this->resolveMethodParameters($method, $routeParams);
        $result = call_user_func_array([$controller, $methodName], $parameters);
        return $result;
    }

    private function resolveMethodParameters(\ReflectionFunctionAbstract $method, array $routeParams) {
        $parameters = [];

        $container = $this->getContainer();

        foreach ($method->getParameters() as $param) {
            $paramName = $param->getName();
            $paramPos  = $param->getPosition();

            if (isset($routeParams[$paramName])) {
                $parameters[$paramPos] = $routeParams[$paramName];
            } else {
                $parameters[$paramPos] = ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
            }

            if ($param->hasType()) {
                $typeClass = new \ReflectionClass($param->getType()->getName());
                $fullName  = $typeClass->getName();
                $shortName = $typeClass->getShortName();

                if ($container->has($fullName)) {
                    $parameters[$paramPos] = $container->get($fullName);
                } elseif ($container->has($shortName)) {
                    $parameters[$paramPos] = $container->get($shortName);
                }
            }
        };

        return $parameters;
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

    public function sendResponse(ResponseInterface $response)
    {
        $http_line = sprintf(
            'HTTP/%s %s %s',
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