<?php

declare(strict_types=1);

namespace Atom\Dispatcher;

use Atom\Router\Router;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Router\MatchedRoute;
use Atom\Router\RouteMatcher;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Http\MiddlewareInterface;
use Atom\Http\RequestHandlerInterface;

final class Dispatcher implements RequestHandlerInterface
{
    public function __construct(
        private Router $router,
        private Injector $injector,
        private RouteInvoker $routeInvoker,
        private ResultConverter $resultConverter,
        private InjectionContext $context
    ) {
    }

    private function getUriPath(Request $request): string
    {
        $scriptName = $request->server()->string("SCRIPT_NAME");
        $scriptDir = pathinfo($scriptName, \PATHINFO_DIRNAME);

        $path1 = explode("/", $scriptDir);
        $path2 = explode("/", $request->getPath());
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

    public function handle(Request $request): Response
    {
        $request = $this->effectiveRequest($request);
        $method = $request->getMethod();
        $uriPath = $this->getUriPath($request);

        $match = (new RouteMatcher($this->router))->match($method, $uriPath, $request->query()->toArray());

        if ($match->isMethodNotAllowed()) {
            return (new Response())
                ->status(405)
                ->header("Allow", implode(", ", $match->allowedMethods))
                ->content("Method Not Allowed");
        }

        if (!$match->isFound()) {
            return (new Response())
                ->status(404)
                ->content("Not Found");
        }

        $matchedRoute = $match->matchedRoute;
        $context = $this->context;
        $context->set(Request::class, $request);
        $context->set(MatchedRoute::class, $matchedRoute);

        $middlewares = $this->resolveMiddlewares($matchedRoute, $context);
        return (new MiddlewarePipeline(
            $middlewares,
            new MatchedRouteHandler($matchedRoute, $this->routeInvoker, $this->resultConverter, $context)
        ))->handle($request);
    }

    private function effectiveRequest(Request $request): Request
    {
        if ($request->getMethod() !== "POST") {
            return $request;
        }

        if (strcasecmp($request->headers()->get("X-Atom-Intent", "") ?? "", "navigate") !== 0) {
            return $request;
        }

        if (strcasecmp($request->headers()->get("X-Atom-Method", "") ?? "", "GET") !== 0) {
            return $request;
        }

        return $request->withMethod("GET");
    }

    private function resolveMiddlewares(MatchedRoute $route, InjectionContext $context): array
    {
        $middlewares = $route->getMiddlewares();
        $results = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $middleware = $this->injector->get($middleware, $context);
            }

            if ($middleware instanceof MiddlewareInterface) {
                $results[] = $middleware;
            } else {
                throw new \RuntimeException("Can't initialize middleware, unsupported definition.");
            }
        }

        return $results;
    }
}
