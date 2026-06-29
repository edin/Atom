<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer\UI\Components;

use Atom\Api\ApiEndpointDescriptor;
use Atom\Api\ApiErrorResponseDescriptor;
use Atom\Api\ApiFieldDescriptor;
use Atom\Modules\ApiExplorer\UI\Models\ApiOperationDescriptor;
use Atom\View\Component\TemplateComponent;

final class EndpointDetails extends TemplateComponent
{
    private const MAX_SCHEMA_DEPTH = 3;

    public ApiOperationDescriptor $operation;
    public int $index;

    public function endpoint(): ApiEndpointDescriptor
    {
        return $this->operation->endpoint;
    }

    /**
     * @return array<string, string>
     */
    public function metaRows(): array
    {
        $endpoint = $this->endpoint();
        $rows = [
            "Method" => $this->operation->method,
            "Handler" => $endpoint->handler,
            "Response" => $endpoint->responseType ?? "mixed",
        ];

        if ($endpoint->name !== null) {
            $rows = ["Name" => $endpoint->name] + $rows;
        }

        if ($endpoint->description !== null) {
            $rows["Description"] = $endpoint->description;
        }

        return $rows;
    }

    public function shortName(string $className): string
    {
        $position = strrpos($className, "\\");

        return $position === false ? $className : substr($className, $position + 1);
    }

    /**
     * @return ApiFieldDescriptor[]
     */
    public function requestParameters(): array
    {
        return array_values(array_filter(
            $this->endpoint()->requestFields,
            fn(ApiFieldDescriptor $field): bool => $field->model === null
        ));
    }

    /**
     * @return array<string, ApiFieldDescriptor[]>
     */
    public function dtoFields(): array
    {
        $groups = [];

        foreach ($this->endpoint()->requestFields as $field) {
            if ($field->model === null) {
                continue;
            }

            $groups[$field->model] ??= [];
            $groups[$field->model][] = $field;
        }

        return $groups;
    }

    public function fieldLabel(ApiFieldDescriptor $field): string
    {
        return $field->sourceName;
    }

    public function locationLabel(ApiFieldDescriptor $field): string
    {
        return $field->source;
    }

    public function rulesLabel(ApiFieldDescriptor $field): string
    {
        return implode(", ", array_map(fn(string $rule): string => $this->shortName($rule), $field->validationRules));
    }

    public function fieldSchema(ApiFieldDescriptor $field): string
    {
        return $this->fieldLabel($field) . ": " . ($field->type ?? "mixed");
    }

    public function fieldType(ApiFieldDescriptor $field): string
    {
        return $this->shortTypeName($field->type ?? "mixed");
    }

    private function shortTypeName(string $type): string
    {
        return preg_replace_callback(
            '/[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+/',
            fn(array $matches): string => $this->shortName($matches[0]),
            $type
        ) ?? $type;
    }

    /**
     * @return ApiFieldDescriptor[]
     */
    public function responseFields(): array
    {
        return $this->endpoint()->responseFields;
    }

    public function responseTitle(): string
    {
        $type = $this->endpoint()->responseType;

        return $type === null ? "mixed" : $this->shortName($type);
    }

    /**
     * @return array<int, array{field: ApiFieldDescriptor, depth: int}>
     */
    public function responseRows(): array
    {
        return $this->flattenResponseFields($this->responseFields());
    }

    /**
     * @param ApiFieldDescriptor[] $fields
     * @return array<int, array{field: ApiFieldDescriptor, depth: int}>
     */
    private function flattenResponseFields(array $fields, int $depth = 0): array
    {
        $rows = [];

        foreach ($fields as $field) {
            $rows[] = ["field" => $field, "depth" => $depth];

            if ($depth + 1 < self::MAX_SCHEMA_DEPTH) {
                array_push($rows, ...$this->flattenResponseFields($field->children, $depth + 1));
            }
        }

        return $rows;
    }

    /**
     * @param array{field: ApiFieldDescriptor, depth: int} $row
     */
    public function responseRowField(array $row): ApiFieldDescriptor
    {
        return $row["field"];
    }

    /**
     * @param array{field: ApiFieldDescriptor, depth: int} $row
     */
    public function responseRowDepth(array $row): int
    {
        return $row["depth"];
    }

    /**
     * @return ApiErrorResponseDescriptor[]
     */
    public function errorResponses(): array
    {
        return $this->endpoint()->errorResponses;
    }

    public function errorTitle(ApiErrorResponseDescriptor $error): string
    {
        return $this->shortName($error->type);
    }

    /**
     * @return array<int, array{field: ApiFieldDescriptor, depth: int}>
     */
    public function errorRows(ApiErrorResponseDescriptor $error): array
    {
        return $this->flattenResponseFields($error->fields);
    }
}
