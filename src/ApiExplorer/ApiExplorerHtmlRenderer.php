<?php

declare(strict_types=1);

namespace Atom\ApiExplorer;

final readonly class ApiExplorerHtmlRenderer
{
    public function render(ApiDescription $description, string $resourcePath): string
    {
        $endpoints = $description->endpoints;

        return "<!doctype html>"
            . "<html lang=\"en\">"
            . "<head>"
            . "<meta charset=\"utf-8\">"
            . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
            . "<title>Atom API Explorer</title>"
            . "<link rel=\"stylesheet\" href=\"" . $this->e($resourcePath) . "/api-explorer.css\">"
            . "</head>"
            . "<body>"
            . "<header class=\"topbar\">"
            . "<div class=\"topbar-inner\">"
            . "<h1>API Explorer</h1>"
            . "<p class=\"count\">" . count($endpoints) . " endpoint" . (count($endpoints) === 1 ? "" : "s") . "</p>"
            . "</div>"
            . "</header>"
            . "<main>"
            . "<div class=\"workspace\">"
            . "<aside class=\"sidebar\">" . $this->navigation($endpoints) . "</aside>"
            . "<section class=\"content\">" . $this->details($endpoints) . "</section>"
            . "<aside class=\"try-panel\">" . $this->tryPanel($endpoints[0] ?? null) . "</aside>"
            . "</div>"
            . "</main>"
            . "</body>"
            . "</html>";
    }

    /**
     * @param ApiEndpointDescriptor[] $endpoints
     */
    private function navigation(array $endpoints): string
    {
        if ($endpoints === []) {
            return "<p class=\"empty\">No endpoints detected.</p>";
        }

        $items = "";
        foreach ($endpoints as $index => $endpoint) {
            $items .= "<a href=\"#endpoint-{$index}\">"
                . "<span>" . $this->e(implode("|", $endpoint->methods)) . "</span>"
                . "<code>" . $this->e($endpoint->path) . "</code>"
                . "</a>";
        }

        return "<h2>Endpoints</h2><nav>{$items}</nav>";
    }

    /**
     * @param ApiEndpointDescriptor[] $endpoints
     */
    private function details(array $endpoints): string
    {
        if ($endpoints === []) {
            return "<section class=\"endpoint\"><p class=\"empty\">No endpoint details to show.</p></section>";
        }

        $html = "";
        foreach ($endpoints as $index => $endpoint) {
            $html .= $this->endpoint($endpoint, $index);
        }

        return $html;
    }

    private function endpoint(ApiEndpointDescriptor $endpoint, int $index): string
    {
        return "<article id=\"endpoint-{$index}\" class=\"endpoint\">"
            . "<div class=\"section-heading\"><span>Description</span></div>"
            . "<div class=\"route-line\">"
            . $this->methods($endpoint)
            . "<code>" . $this->e($endpoint->path) . "</code>"
            . "</div>"
            . $this->meta($endpoint)
            . "<div class=\"section-heading\"><span>Request shape</span></div>"
            . $this->fields($endpoint)
            . "</article>";
    }

    private function methods(ApiEndpointDescriptor $endpoint): string
    {
        return "<span class=\"methods\">" . implode("", array_map(
            fn(string $method): string => "<b>" . $this->e($method) . "</b>",
            $endpoint->methods
        )) . "</span>";
    }

    private function meta(ApiEndpointDescriptor $endpoint): string
    {
        $rows = [
            "Handler" => $endpoint->handler,
            "Response" => $endpoint->responseType ?? "mixed",
        ];

        if ($endpoint->name !== null) {
            $rows = ["Name" => $endpoint->name] + $rows;
        }

        if ($endpoint->description !== null) {
            $rows["Description"] = $endpoint->description;
        }

        $html = "";
        foreach ($rows as $label => $value) {
            $html .= "<dt>" . $this->e($label) . "</dt><dd>" . $this->e($value) . "</dd>";
        }

        return "<dl>{$html}</dl>";
    }

    private function fields(ApiEndpointDescriptor $endpoint): string
    {
        if ($endpoint->requestFields === []) {
            return "<p class=\"empty\">No request fields detected.</p>";
        }

        $rows = "";
        foreach ($endpoint->requestFields as $field) {
            $rules = $field->validationRules === []
                ? ""
                : "<small>" . $this->e(implode(", ", array_map(fn(string $rule): string => $this->shortName($rule), $field->validationRules))) . "</small>";

            $rows .= "<tr>"
                . "<td><code>" . $this->e($field->source . "." . $field->sourceName) . "</code></td>"
                . "<td>" . $this->e($field->type ?? "mixed") . "</td>"
                . "<td>" . ($field->required ? "yes" : "no") . "</td>"
                . "<td>" . $this->e($field->model !== null ? $this->shortName($field->model) : "") . "</td>"
                . "<td>{$rules}</td>"
                . "</tr>";
        }

        return "<table>"
            . "<thead><tr><th>Field</th><th>Type</th><th>Required</th><th>Model</th><th>Rules</th></tr></thead>"
            . "<tbody>{$rows}</tbody>"
            . "</table>";
    }

    private function tryPanel(?ApiEndpointDescriptor $endpoint): string
    {
        if ($endpoint === null) {
            return "<h2>Try request</h2><p class=\"empty\">Select an endpoint after routes are available.</p>";
        }

        $methodOptions = "";
        foreach ($endpoint->methods as $method) {
            $methodOptions .= "<option>" . $this->e($method) . "</option>";
        }

        return "<h2>Try request</h2>"
            . "<form class=\"request-form\">"
            . "<label>Method<select>{$methodOptions}</select></label>"
            . "<label>URL<input value=\"" . $this->e($endpoint->path) . "\"></label>"
            . "<label>Query<textarea placeholder=\"q=atom&amp;page=1\"></textarea></label>"
            . "<label>Body<textarea class=\"body-editor\" placeholder=\"{&#10;  &quot;name&quot;: &quot;Atom&quot;&#10;}\"></textarea></label>"
            . "<button type=\"button\">Send</button>"
            . "</form>"
            . "<div class=\"response-preview\">"
            . "<div class=\"section-heading\"><span>Response</span></div>"
            . "<pre>{\n  \"status\": \"not sent yet\"\n}</pre>"
            . "</div>";
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }

    private function shortName(string $className): string
    {
        $position = strrpos($className, "\\");

        return $position === false ? $className : substr($className, $position + 1);
    }
}
