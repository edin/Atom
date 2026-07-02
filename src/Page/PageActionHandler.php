<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Di\Exception\DependencyResolutionException;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Http\Request;
use Atom\Router\MatchedRoute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

final readonly class PageActionHandler
{
    public function __construct(
        private Injector $injector,
        private InjectionContext $context,
        private PageStateSerializer $state = new JsonPageStateSerializer(),
        private PageInputHydratorInterface $input = new PageInputHydrator(),
        private PageActionParameterBinder $parameters = new PageActionParameterBinder()
    ) {
    }

    public function handle(PageRenderer $pages, MatchedRoute $route, Request $request): mixed
    {
        $metadata = $route->getRouteEntry()->getMetadataOfType(PageRouteMetadata::class);

        if (!$metadata instanceof PageRouteMetadata) {
            throw new PageActionException("Page route metadata is missing.");
        }

        $page = $this->injector->instantiate($metadata->pageClass, context: $this->context);
        $this->state->deserialize($page, $request->post()->string("_state"));
        $this->input->hydrate($page, $request, $route);
        $this->invokeGet($page, $route);
        $call = $this->parseAction($request->post()->string("_action", "default"));
        $action = $this->resolveAction($page, $request, $call);
        $parameters = $this->parameters->bind($page, $request, $route, $call, $action);

        try {
            $result = $this->injector->invoke(
                [$page, $action->getName()],
                $parameters,
                $this->context
            );
        } catch (DependencyResolutionException $exception) {
            throw new PageActionException(
                "Unable to invoke page action '{$call->name}' on " . $page::class . "::" . $action->getName() .
                "(). " . $exception->getMessage(),
                previous: $exception
            );
        }

        return $result ?? $pages->renderPage($page, false);
    }

    private function invokeGet(Page $page, MatchedRoute $route): void
    {
        $reflection = new ReflectionClass($page);
        if (!$reflection->hasMethod("get")) {
            return;
        }

        $this->injector->invoke([$page, "get"], $route->getRouteParams(), $this->context);
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

        $methods = $this->availableHttpMethods($reflection, $call->name);
        if ($methods !== []) {
            throw new PageActionException(
                "Page action '{$call->name}' on {$reflection->getName()} is not available for " .
                strtoupper($request->getMethod()) . ". Available method" . (count($methods) === 1 ? "" : "s") .
                ": " . implode(", ", $methods) . "."
            );
        }

        $actions = $this->availableActionNames($reflection);

        throw new PageActionException(
            "Page action '{$call->name}' was not found on {$reflection->getName()}." .
            ($actions === [] ? "" : " Available actions: " . implode(", ", $actions) . ".")
        );
    }

    private function parseAction(string $expression): PageActionCall
    {
        $expression = trim($expression);
        if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(?:\((.*)\))?$/', $expression, $matches)) {
            throw new PageActionException(
                "Page action expression '{$expression}' is invalid. Expected syntax like 'save' or 'delete(12, \"hard\")'."
            );
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

        foreach ($this->argumentTokens($source) as $token) {
            $token = trim($token);
            if ($token === "") {
                throw new PageActionException("Page action arguments are invalid. Empty arguments are not supported.");
            }

            $arguments[] = $this->parseArgument($token);
        }

        return $arguments;
    }

    /**
     * @return string[]
     */
    private function argumentTokens(string $source): array
    {
        $tokens = [];
        $token = "";
        $quote = null;
        $escaped = false;
        $length = strlen($source);

        for ($index = 0; $index < $length; $index++) {
            $char = $source[$index];

            if ($escaped) {
                $token .= "\\" . $char;
                $escaped = false;
                continue;
            }

            if ($char === "\\") {
                $escaped = true;
                continue;
            }

            if ($quote !== null) {
                $token .= $char;
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
                $token .= $char;
                continue;
            }

            if ($char === ",") {
                $tokens[] = $token;
                $token = "";
                continue;
            }

            $token .= $char;
        }

        if ($escaped || $quote !== null) {
            throw new PageActionException("Page action arguments are invalid. Quotes or escapes are not closed.");
        }

        $tokens[] = $token;

        return $tokens;
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
            default => $this->parseNumber($value),
        };
    }

    private function parseNumber(string $value): mixed
    {
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * @return string[]
     */
    private function availableActionNames(ReflectionClass $reflection): array
    {
        $names = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $candidate) {
            foreach ($candidate->getAttributes(PageAction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $action = $attribute->newInstance();
                $names[] = $action->name ?? $candidate->getName();
            }
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }

    /**
     * @return string[]
     */
    private function availableHttpMethods(ReflectionClass $reflection, string $actionName): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $candidate) {
            foreach ($candidate->getAttributes(PageAction::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $action = $attribute->newInstance();
                if (($action->name ?? $candidate->getName()) === $actionName) {
                    $methods[] = strtoupper($action->method);
                }
            }
        }

        $methods = array_values(array_unique($methods));
        sort($methods);

        return $methods;
    }

}
