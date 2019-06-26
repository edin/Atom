<?php

namespace Atom\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use function strlen, rtrim, substr, pathinfo, rawurldecode;

final class Dispatcher
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    public function dispatch(ServerRequestInterface $request): ResponseInterface
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
}
