<?php

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Dispatcher\RequestHandler;
use Atom\Dispatcher\RouteHandler;
use Atom\Container\Container;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function strlen;
use function rtrim;
use function substr;
use function pathinfo;
use function rawurldecode;

final class Dispatcher implements RequestHandlerInterface
{
    private $container;
    private $router;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->router = $container->Router;
    }

    final private function getRouteDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $collector) {
            $routes = $this->router->getAllRoutes();
            foreach ($routes as $route) {
                $collector->addRoute($route->getMethod(), $route->getFullPath(), $route);
            }
        });
        return $dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        $serverParams = $request->getServerParams();

        $scriptName = $serverParams['SCRIPT_NAME'] ?? "";
        $scriptDir = pathinfo($scriptName, \PATHINFO_DIRNAME);

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
        } elseif ($uriPath[0] !== "/") {
            $uriPath = "/" . $uriPath;
        }

        $dispatcher = $this->getRouteDispatcher();
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
                $routeParams = $routeInfo[2];

                //TODO: Attach routeParams to route

                $middlewares = $this->resolveMiddlewares($route);
                $queueHandler = new RequestHandler(new RouteHandler($this->container, $route, $routeParams));
                $queueHandler->addMiddlewares($middlewares);
                return $queueHandler->handle($request);
        }
    }

    private function resolveMiddlewares(Route $route): array
    {
        $middlewares = $route->getMiddlewares();
        $results = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                //TODO: Use DI to create middleware
                $results[] = new $middleware;
            } elseif (is_object($middleware)) {
                $results[] = $middleware;
            } else {
                throw new \Exception("Can't initialize middleware, unsupported definition.");
            }
        }

        return $results;
    }
}
