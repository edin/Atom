<?php

namespace Atom;

use Atom\Container\Container;
use Atom\Router\Router;
use Atom\Router\Route;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;


abstract class Application //implements RequestHandlerInterface
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

    final public function getDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $collector) {
            $routes = $this->getRouter()->getAllRoutes();
            foreach ($routes as $route) {
                $collector->addRoute($route->method, $route->getFullPath(), $route);
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

        $di->Container = function () {
            return $this->getContainer();
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

        $di->ViewEngine = function ($di) {
            $engine = new \Atom\View\ViewEngine($di->View);
            return $engine;
        };
    }

    public function registerRoutes(Router $router)
    {
    }

    public function registerServices(Container $container)
    {
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        $serverParams = $request->getServerParams();

        $scriptName = $serverParams['SCRIPT_NAME'] ?? "";
        $scriptDir = \pathinfo($scriptName, PATHINFO_DIRNAME);

        $this->baseUrl = rtrim($scriptDir, " /") . "/";

        $uriPath = $uri->getPath();

        if (false !== $pos = strpos($uriPath, '?')) {
            $uriPath = substr($uriPath, 0, $pos);
        }

        $size = strlen($scriptDir);
        $uriPath = substr($uriPath, $size);
        $uriPath = rawurldecode($uriPath);
        if ($uriPath == "") {
            $uriPath = "/";
        } else if ($uriPath[0] !== "/") {
            $uriPath = "/" . $uriPath;
        }

        $dispatcher = $this->getDispatcher();
        $routeInfo = $dispatcher->dispatch($method, $uriPath);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                throw new \Exception("Route '$uriPath' was not found.");
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $allowedMethodsStr = implode(", ", $allowedMethods);
                throw new \Exception("Method $method is not allowed. Allowed methods are $allowedMethodsStr.");
            case \FastRoute\Dispatcher::FOUND:
                $route = $routeInfo[1];
                $vars = $routeInfo[2];
                $result = $this->executeHandler($request, $route, $vars);
                return $result;
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
        $response->getBody()->write((string) $result);
        return $response;
    }

    private function resolveMiddlewares(Route $route): array {
        $middlewares = $route->getGroup()->getMiddlewares();
        $results = [];

        foreach($middlewares as $middleware) {

            if (is_string($middleware)) {
                $results[] = new $middleware;
            }
            else if (is_object($middleware)) {
                $results[] = $middleware;
            }
            else {
                throw new \Exception("Can't initialize middleware, unsupported definition.");
            }
        }

        return $results;
    }

    public function executeHandler(RequestInterface $request, Route $route, array $routeParams)
    {
        $middlewares =  $this->resolveMiddlewares($route);
        $queueHandler = new QueueRequestHandler(new RouteHandler($this, $route, $routeParams));
        $queueHandler->add($middlewares);

        return $queueHandler->handle($request);
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
        $response = $this->handle($this->getRequest());
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

class RouteHandler implements RequestHandlerInterface
{
    private $app;
    private $route;
    private $routeParams;

    public function __construct(Application $app, Route $route, array $routeParams)
    {
        $this->app = $app;
        $this->route = $route;
        $this->routeParams = $routeParams;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->app->getContainer();
        $route = $this->route;
        $routeParams = $this->routeParams;

        if ($route->handler instanceof \Closure) {
            $method = new \ReflectionFunction($route->handler);
            $parameters = $container->resolveMethodParameters($method, $routeParams);
            $result = call_user_func_array($route->handler, $parameters);
            return $this->app->processResult($result);
        }

        $parts = \explode("@", $route->handler);
        $controller = $parts[0] ?? "";
        $methodName = $parts[1] ?? "index";

        $controller = $this->app->resolveController($controller);

        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod($methodName);

        if ($method == null) {
            throw new \Exception("Class {$reflectionClass->getName()} does not contain method {$methodName}.");
        }

        $container->resolveProperties($controller);
        $parameters = $container->resolveMethodParameters($method, $routeParams);
        $result = call_user_func_array([$controller, $methodName], $parameters);
        return $this->app->processResult($result);
    }
}

class QueueRequestHandler implements RequestHandlerInterface
{
    private $middleware = [];
    private $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    public function add(array $middleware)
    {
        $this->middleware = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middleware) === 0) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = array_shift($this->middleware);
        return $middleware->process($request, $this);
    }
}