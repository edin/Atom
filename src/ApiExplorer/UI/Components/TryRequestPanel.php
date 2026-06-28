<?php

declare(strict_types=1);

namespace Atom\ApiExplorer\UI\Components;

use Atom\ApiExplorer\ApiFieldDescriptor;
use Atom\ApiExplorer\UI\ApiOperationDescriptor;
use Atom\View\Component\TemplateComponent;

final class TryRequestPanel extends TemplateComponent
{
    public ?ApiOperationDescriptor $operation = null;
    public int $selectedId = 0;
    public ?string $url = null;
    public ?string $body = null;
    public ?string $responsePreview = null;

    public function requestUrl(): string
    {
        return $this->url ?? $this->operation?->path() ?? "/";
    }

    public function requestBody(): string
    {
        return $this->body ?? $this->defaultBody();
    }

    public function responseText(): string
    {
        return $this->responsePreview ?? json_encode(["status" => "not sent yet"], JSON_PRETTY_PRINT);
    }

    private function defaultBody(): string
    {
        if ($this->operation === null) {
            return "{}";
        }

        $body = [];
        foreach ($this->operation->endpoint->requestFields as $field) {
            if ($field->source === "body") {
                $body[$field->sourceName] = $this->sampleValue($field);
            }
        }

        return json_encode((object) $body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: "{}";
    }

    private function sampleValue(ApiFieldDescriptor $field): mixed
    {
        $type = ltrim($field->type ?? "string", "?");

        return match ($type) {
            "int" => 1,
            "float" => 1.0,
            "bool" => false,
            "array" => [],
            default => match (true) {
                str_contains(strtolower($field->name), "title") => "Sample title",
                str_contains(strtolower($field->name), "summary") => "Sample summary",
                default => "Sample value",
            },
        };
    }
}
