<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Router\Route;
use Atom\Router\Router;
use Atom\Container\Container;
use Atom\Dispatcher\RouteHandler;
use Atom\Dispatcher\RequestHandler;
use Atom\Router\Action;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Dispatcher implements RequestHandlerInterface
{
    private Container $container;
    private Router $router;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->router = $container->Router;
    }

    private function getRouteDispatcher()
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $collector) {
            $routes = $this->router->getAllRoutes();
            foreach ($routes as $route) {
                $collector->addRoute($route->getMethod(), $route->getFullPath(), $route);
            }
        });
        return $dispatcher;
    }

    private function getUriPath(ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $serverParams = $request->getServerParams();

        $scriptName = $serverParams['SCRIPT_NAME'] ?? "";
        $scriptDir = pathinfo($scriptName, \PATHINFO_DIRNAME);

        $path1 = explode("/", $scriptDir);
        $path2 = explode("/", $uri->getPath());
        $diff = array_diff_assoc($path2, $path1);

        $uriPath = implode("/", $diff);

        if (false !== $pos = strpos($uriPath, '?')) {
            $uriPath = substr($uriPath, 0, $pos);
        }

        $uriPath = rawurldecode($uriPath);
        if ($uriPath == "") {
            $uriPath = "/";
        } elseif ($uriPath[0] !== "/") {
            $uriPath = "/" . $uriPath;
        }

        return $uriPath;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uriPath = $this->getUriPath($request);

        $dispatcher = $this->getRouteDispatcher();
        $routeInfo = $dispatcher->dispatch($method, $uriPath);

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                throw new \RuntimeException("Route '$uriPath' was not found.");
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $allowedMethodsStr = implode(", ", $allowedMethods);
                throw new \RuntimeException("Method $method is not allowed. Allowed methods are $allowedMethodsStr.");
            case \FastRoute\Dispatcher::FOUND:
                $route = $routeInfo[1];
                $routeParams = $routeInfo[2];

                if ($route instanceof Route) {
                    $route->setRouteParams($routeParams);
                    $route->setQueryParams($request->getQueryParams());

                    $this->container->bind(get_class($route))->toInstance($route);

                    $action = new Action($this->container, $route);
                    $this->container->bind(get_class($action))->toInstance($action);

                    $middlewares = $this->resolveMiddlewares($route);
                    $queueHandler = new RequestHandler(new RouteHandler($this->container, $action));
                    $queueHandler->addMiddlewares($middlewares);
                    return $queueHandler->handle($request);
                }
        }
        throw new \RuntimeException("Failed to handle request to '$uriPath' path.");
    }

    private function resolveMiddlewares(Route $route): array
    {
        $context = $this->container->RequestScope;
        $middlewares = $route->getMiddlewares();
        $results = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $results[] = $this->container->resolveInContext($context, $middleware);
            } elseif (is_object($middleware)) {
                $results[] = $middleware;
            } else {
                throw new \RuntimeException("Can't initialize middleware, unsupported definition.");
            }
        }

        return $results;
    }
}
