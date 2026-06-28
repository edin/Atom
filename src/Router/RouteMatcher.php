<?php

declare(strict_types=1);

namespace Atom\Router;

final class RouteMatcher
{
    public function __construct(private Router $router)
    {
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public function match(string $method, string $path, array $queryParams = []): RouteMatchResult
    {
        $allowedMethods = [];

        foreach ($this->router->getAllRoutes() as $route) {
            $routeParams = $this->matchPath($route->getFullPath(), $path);

            if ($routeParams === null) {
                continue;
            }

            $routeMethods = $this->normalizeMethods($route->getMethod());

            if (in_array(strtoupper($method), $routeMethods, true)) {
                return RouteMatchResult::found(new MatchedRoute($route, $routeParams, $queryParams));
            }

            $allowedMethods = array_merge($allowedMethods, $routeMethods);
        }

        if (count($allowedMethods) > 0) {
            return RouteMatchResult::methodNotAllowed($allowedMethods);
        }

        return RouteMatchResult::notFound();
    }

    /**
     * @return array<string, string>|null
     */
    private function matchPath(string $routePath, string $requestPath): ?array
    {
        $routeSegments = $this->splitPath($routePath);
        $requestSegments = $this->splitPath($requestPath);

        if (!$this->segmentCountsCanMatch($routeSegments, $requestSegments)) {
            return null;
        }

        $params = [];

        foreach ($routeSegments as $index => $routeSegment) {
            $requestSegment = $requestSegments[$index];

            if ($this->isWildcardParameterSegment($routeSegment)) {
                $params[substr($routeSegment, 1, -2)] = rawurldecode(implode("/", array_slice($requestSegments, $index)));
                return $params;
            }

            if ($this->isParameterSegment($routeSegment)) {
                $params[substr($routeSegment, 1, -1)] = rawurldecode($requestSegment);
                continue;
            }

            if ($routeSegment !== $requestSegment) {
                return null;
            }
        }

        return $params;
    }

    /**
     * @return string[]
     */
    private function splitPath(string $path): array
    {
        $path = trim($path, " /");

        if ($path === "") {
            return [];
        }

        return explode("/", $path);
    }

    private function isParameterSegment(string $segment): bool
    {
        return preg_match('/^\{[a-zA-Z_][a-zA-Z0-9_]*\}$/', $segment) === 1;
    }

    private function isWildcardParameterSegment(string $segment): bool
    {
        return preg_match('/^\{[a-zA-Z_][a-zA-Z0-9_]*\*\}$/', $segment) === 1;
    }

    /**
     * @param string[] $routeSegments
     * @param string[] $requestSegments
     */
    private function segmentCountsCanMatch(array $routeSegments, array $requestSegments): bool
    {
        $last = $routeSegments[array_key_last($routeSegments)] ?? null;
        if (is_string($last) && $this->isWildcardParameterSegment($last)) {
            return count($requestSegments) >= count($routeSegments);
        }

        return count($routeSegments) === count($requestSegments);
    }

    /**
     * @return string[]
     */
    private function normalizeMethods(string|array $method): array
    {
        $methods = is_array($method) ? $method : [$method];

        return array_map(static fn (string $method): string => strtoupper($method), $methods);
    }
}
