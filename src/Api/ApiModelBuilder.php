<?php

declare(strict_types=1);

namespace Atom\Api;

use Atom\Api\Attributes\ArrayOf;
use Atom\Api\Attributes\ErrorResponse;
use Atom\Api\Attributes\ResponseOf;
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\SourceAttributeInterface;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
use Atom\Validation\Rules\Required;
use Atom\Validation\ValidationRuleInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;

final class ApiModelBuilder
{
    private const MAX_SCHEMA_DEPTH = 3;

    public function describe(Router $router, ?string $pathPrefix = null): ApiDescription
    {
        $routes = array_filter(
            $router->getAllRoutes(),
            fn(RouteEntry $route): bool =>
                $route->getMetadataOfType(ApiHidden::class) === null
                && $this->matchesPathPrefix($route, $pathPrefix)
        );

        return new ApiDescription(array_map(
            fn(RouteEntry $route): ApiEndpointDescriptor => $this->describeRoute($route),
            array_values($routes)
        ));
    }

    private function matchesPathPrefix(RouteEntry $route, ?string $pathPrefix): bool
    {
        if ($pathPrefix === null || trim($pathPrefix) === "") {
            return true;
        }

        $prefix = "/" . trim($pathPrefix, " /");
        $path = "/" . trim($route->getFullPath(), " /");

        return $path === $prefix || str_starts_with($path, $prefix . "/");
    }

    private function describeRoute(RouteEntry $route): ApiEndpointDescriptor
    {
        $handler = $this->reflectHandler($route->getRouteAction());

        return new ApiEndpointDescriptor(
            $this->normalizeMethods($route->getMethod()),
            $route->getFullPath(),
            $route->getName(),
            $route->getTitle(),
            $route->getDescription(),
            $this->handlerName($route->getRouteAction()),
            $route->getController(),
            $route->getMethodName(),
            $this->typeName($handler->getReturnType()),
            $this->fieldsFromHandler($handler, $route),
            $this->fieldsFromReturnType($handler->getReturnType(), $this->responseType($handler)),
            $this->errorResponses($handler)
        );
    }

    private function reflectHandler(RouteAction $action): ReflectionFunctionAbstract
    {
        if ($action->isClosure()) {
            return new ReflectionFunction($action->closure);
        }

        if ($action->controllerType === null || $action->methodName === null) {
            throw new RuntimeException("Controller action must define controller type and method name.");
        }

        return new ReflectionMethod($action->controllerType, $action->methodName);
    }

    /**
     * @return ApiFieldDescriptor[]
     */
    private function fieldsFromHandler(ReflectionFunctionAbstract $handler, RouteEntry $route): array
    {
        $fields = [];
        $routeParameters = $this->routeParameters($route->getFullPath());

        foreach ($handler->getParameters() as $parameter) {
            $className = $this->parameterClassName($parameter);

            if ($className !== null && $this->isDto($className)) {
                array_push($fields, ...$this->fieldsFromDto($className));
                continue;
            }

            if ($className !== null) {
                continue;
            }

            $fields[] = $this->fieldFromParameter($parameter, in_array($parameter->getName(), $routeParameters, true));
        }

        return $fields;
    }

    /**
     * @param class-string $className
     * @return ApiFieldDescriptor[]
     */
    private function fieldsFromDto(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $fields = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || $property->isReadOnly()) {
                continue;
            }

            $source = $this->source($property);
            $fields[] = new ApiFieldDescriptor(
                $property->getName(),
                $source["source"],
                $source["name"] ?? $property->getName(),
                $this->typeName($property->getType()),
                $this->isPropertyRequired($property),
                $className,
                $this->validationRules($property)
            );
        }

        return $fields;
    }

    private function fieldFromParameter(ReflectionParameter $parameter, bool $routeParameter): ApiFieldDescriptor
    {
        $source = $this->source($parameter);
        $sourceName = $source["name"] ?? $parameter->getName();

        return new ApiFieldDescriptor(
            $parameter->getName(),
            $source["source"] !== "auto" ? $source["source"] : ($routeParameter ? "route" : "auto"),
            $sourceName,
            $this->typeName($parameter->getType()),
            !$parameter->isOptional() && !($parameter->getType()?->allowsNull() ?? true),
            null
        );
    }

    /**
     * @return ApiFieldDescriptor[]
     */
    private function fieldsFromReturnType(?ReflectionType $type, ?string $responseType): array
    {
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return [];
        }

        $className = $type->getName();
        if (!class_exists($className)) {
            return [];
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isInternal()) {
            return [];
        }

        return $this->fieldsFromClass($className, $responseType);
    }

    /**
     * @param class-string $className
     * @param array<string, true> $visited
     * @return ApiFieldDescriptor[]
     */
    private function fieldsFromClass(string $className, ?string $responseType, array $visited = [], int $depth = 0): array
    {
        if ($depth >= self::MAX_SCHEMA_DEPTH || isset($visited[$className])) {
            return [];
        }

        $visited[$className] = true;
        $reflection = new ReflectionClass($className);
        $fields = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $arrayItemType = $this->arrayItemType($property, $responseType);
            $propertyType = $this->propertyTypeName($property, $arrayItemType);
            $nestedType = $arrayItemType ?? $this->propertyClassName($property);

            $fields[] = new ApiFieldDescriptor(
                $property->getName(),
                "response",
                $property->getName(),
                $propertyType,
                $this->isPropertyRequired($property),
                $className,
                [],
                $nestedType !== null ? $this->fieldsFromClassName($nestedType, $responseType, $visited, $depth + 1) : []
            );
        }

        return $fields;
    }

    /**
     * @param array<string, true> $visited
     * @return ApiFieldDescriptor[]
     */
    private function fieldsFromClassName(string $type, ?string $responseType, array $visited, int $depth): array
    {
        if (!class_exists($type)) {
            return [];
        }

        $reflection = new ReflectionClass($type);
        if ($reflection->isInternal()) {
            return [];
        }

        return $this->fieldsFromClass($type, $responseType, $visited, $depth);
    }

    private function responseType(ReflectionFunctionAbstract $handler): ?string
    {
        $attributes = $handler->getAttributes(ResponseOf::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance()->type;
    }

    /**
     * @return ApiErrorResponseDescriptor[]
     */
    private function errorResponses(ReflectionFunctionAbstract $handler): array
    {
        $responses = [];

        foreach ($handler->getAttributes(ErrorResponse::class) as $attribute) {
            $error = $attribute->newInstance();
            $responses[] = new ApiErrorResponseDescriptor(
                $error->status,
                $error->type,
                $error->description,
                $this->fieldsFromClassName($error->type, $this->responseType($handler), [], 0)
            );
        }

        return $responses;
    }

    /**
     */
    private function arrayItemType(ReflectionProperty $property, ?string $responseType): ?string
    {
        $attributes = $property->getAttributes(ArrayOf::class);
        if ($attributes === []) {
            return null;
        }

        $type = $attributes[0]->newInstance()->type;

        return $type ?? $responseType;
    }

    private function propertyTypeName(ReflectionProperty $property, ?string $arrayItemType): ?string
    {
        if ($arrayItemType !== null) {
            return $this->shortName($arrayItemType) . "[]";
        }

        return $this->typeName($property->getType());
    }

    private function propertyClassName(ReflectionProperty $property): ?string
    {
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    /**
     * @return array{source: string, name?: string}
     */
    private function source(ReflectionParameter|ReflectionProperty $reflection): array
    {
        foreach ($reflection->getAttributes(SourceAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $source = $attribute->newInstance();
            $name = property_exists($source, "name") ? $source->name : null;

            return $name === null
                ? ["source" => $source->source()]
                : ["source" => $source->source(), "name" => $name];
        }

        return ["source" => "auto"];
    }

    private function parameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    /**
     * @param class-string $className
     */
    private function isDto(string $className): bool
    {
        return (new ReflectionClass($className))->getAttributes(Dto::class) !== [];
    }

    private function isPropertyRequired(ReflectionProperty $property): bool
    {
        if ($property->getAttributes(Required::class) !== []) {
            return true;
        }

        return !$property->hasDefaultValue() && !($property->getType()?->allowsNull() ?? true);
    }

    /**
     * @return string[]
     */
    private function validationRules(ReflectionProperty $property): array
    {
        $rules = [];

        foreach ($property->getAttributes(ValidationRuleInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $rules[] = $attribute->getName();
        }

        return $rules;
    }

    private function typeName(?ReflectionType $type): ?string
    {
        return match (true) {
            $type instanceof ReflectionNamedType => ($type->allowsNull() && $type->getName() !== "mixed" ? "?" : "") . $type->getName(),
            $type instanceof ReflectionUnionType => implode("|", array_map(fn(ReflectionType $type): string => $this->typeName($type) ?? "mixed", $type->getTypes())),
            $type instanceof ReflectionIntersectionType => implode("&", array_map(fn(ReflectionType $type): string => $this->typeName($type) ?? "mixed", $type->getTypes())),
            default => null,
        };
    }

    private function shortName(string $className): string
    {
        $position = strrpos($className, "\\");

        return $position === false ? $className : substr($className, $position + 1);
    }

    /**
     * @return string[]
     */
    private function normalizeMethods(string|array $method): array
    {
        return array_map(static fn(string $method): string => strtoupper($method), is_array($method) ? $method : [$method]);
    }

    private function handlerName(RouteAction $action): string
    {
        if ($action->controllerType !== null && $action->methodName !== null) {
            return $action->controllerType . "::" . $action->methodName;
        }

        return "Closure";
    }

    /**
     * @return string[]
     */
    private function routeParameters(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $matches);

        return $matches[1] ?? [];
    }
}
