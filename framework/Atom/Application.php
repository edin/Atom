<?php

namespace Atom;

use Atom\Container\Container;
use Atom\Router\Router;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class Application
{
    public static $app = null;
    private $container = null;
    protected $baseUrl = "";

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

    final public function getRequest(): RequestInterface
    {
        return $this->getContainer()->Request;
    }

    final public function getResponse(): ResponseInterface
    {
        return $this->getContainer()->Response;
    }

    final public function getDispatcher(): RequestHandlerInterface
    {
        return $this->getContainer()->Dispatcher;
    }

    public function registerDefaultServices()
    {
        $di = $this->getContainer();

        $di->Router = Router::class;
        $di->Application = $this;
        $di->bind(Container::class)->toInstance($di);

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

        $di->ViewEngine = \Atom\View\ViewEngine::class;

        $di->{"Psr\Http\Message\RequestInterface"} = function($di) {
            return $di->Request;
        };


        $di->{"Psr\Http\Message\ResponseInterface"} = function($di) {
            return $di->Response;
        };

        // $di->ViewEngine = function ($di) {
        //     $engine = new \Atom\View\ViewEngine($di->View);
        //     return $engine;
        // };

        // $di->Dispatcher = function ($di) {
        //     return new \Atom\Dispatcher\Dispatcher($di);
        // };

        // $di->ResultHandler = function ($di) {
        //     return new \Atom\Dispatcher\ResultHandler($di);
        // };

        $di->ViewEngine = \Atom\View\ViewEngine::class;
        $di->Dispatcher = \Atom\Dispatcher\Dispatcher::class;
        $di->ResultHandler = \Atom\Dispatcher\ResultHandler::class;
    }

    public abstract function registerRoutes(Router $router);
    public abstract function registerServices(Container $container);

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
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

        $container = $this->getContainer();
        //$container->resolveProperties($controller);

        $parameters = $container->resolveMethodParameters($method, $routeParams);
        $result = call_user_func_array([$controller, $methodName], $parameters);
        return $result;
    }

    public function resolveController($name)
    {
        return $this->getContainer()->get($name);
    }

    public function initialize()
    {
        $this->registerDefaultServices();
        $this->registerServices($this->getContainer());
        $this->registerRoutes($this->getRouter());
    }

    public function run()
    {
        $this->initialize();
        $this->sendResponse($this->getDispatcher()->handle($this->getRequest()));
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
