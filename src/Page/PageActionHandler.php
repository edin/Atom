<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Router\MatchedRoute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final readonly class PageActionHandler
{
    public function __construct(
        private Injector $injector,
        private InjectionContext $context,
        private PageStateSerializer $state = new JsonPageStateSerializer()
    ) {
    }

    public function handle(PageRenderer $pages, MatchedRoute $route, Request $request): mixed
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(PageRouteMetadata::class);

        if (!$metadata instanceof PageRouteMetadata) {
            throw new RuntimeException("Page route metadata is missing.");
        }

        $page = $this->injector->instantiate($metadata->pageClass, context: $this->context);
        $this->state->deserialize($page, $request->post()->string("_state"));
        $call = $this->parseAction($request->post()->string("_action", "default"));
        $action = $this->resolveAction($page, $request, $call);
        $result = $this->injector->invoke(
            [$page, $action->getName()],
            $this->actionParameters($route, $call, $action),
            $this->context
        );

        return $result ?? $pages->renderPage($page, false);
    }

    private function resolveAction(Page $page, Request $request, PageActionCall $call): ReflectionMethod
    {
        $method = strtolower($request->getMethod());
        $reflection = new ReflectionClass($page);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $candidate) {
            foreach ($candidate->getAttributes(PageAction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $action = $attribute->newInstance();
                $actionName = $action->name ?? $candidate->getName();

                if (strtolower($action->method) === $method && $actionName === $call->name) {
                    return $candidate;
                }
            }
        }

        throw new RuntimeException("Page action '{$call->name}' was not found.");
    }

    /**
     * @return array<string, mixed>
     */
    private function actionParameters(MatchedRoute $route, PageActionCall $call, ReflectionMethod $method): array
    {
        $parameters = $route->getParams();

        foreach ($call->arguments as $index => $argument) {
            $reflectionParameter = $method->getParameters()[$index] ?? null;
            if ($reflectionParameter !== null) {
                $parameters[$reflectionParameter->getName()] = $argument;
            }
        }

        return $parameters;
    }

    private function parseAction(string $expression): PageActionCall
    {
        $expression = trim($expression);
        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(?:\((.*)\))?$/', $expression, $matches)) {
            throw new RuntimeException("Page action expression '{$expression}' is invalid.");
        }

        return new PageActionCall($matches[1], $this->parseArguments($matches[2] ?? ""));
    }

    /**
     * @return array<int, mixed>
     */
    private function parseArguments(string $source): array
    {
        $source = trim($source);
        if ($source === "") {
            return [];
        }

        $arguments = [];
        $tokens = str_getcsv($source, ",", "\"", "\\");

        foreach ($tokens as $token) {
            $arguments[] = $this->parseArgument(trim($token));
        }

        return $arguments;
    }

    private function parseArgument(string $value): mixed
    {
        if (preg_match("/^'(.*)'$/s", $value, $matches)) {
            return stripcslashes($matches[1]);
        }

        if (preg_match('/^"(.*)"$/s', $value, $matches)) {
            return stripcslashes($matches[1]);
        }

        return match (strtolower($value)) {
            "true" => true,
            "false" => false,
            "null" => null,
            default => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $value,
        };
    }
}
