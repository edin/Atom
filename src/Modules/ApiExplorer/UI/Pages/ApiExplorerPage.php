<?php

declare(strict_types=1);

namespace Atom\Modules\ApiExplorer\UI\Pages;

use Atom\Api\ApiDescription;
use Atom\Modules\ApiExplorer\ApiExplorerOptions;
use Atom\Api\ApiModelBuilder;
use Atom\Modules\ApiExplorer\UI\Models\ApiOperationDescriptor;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Dispatcher\Dispatcher;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Page\Page;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Router\MatchedRoute;
use Atom\Router\Router;
use Throwable;

#[PageRoute("/explorer")]
final class ApiExplorerPage extends Page
{
    public string $title = "API Explorer";
    public ApiDescription $description;
    /** @var ApiOperationDescriptor[] */
    public array $operations = [];
    public int $selectedId = 0;
    public ?string $requestUrl = null;
    public ?string $requestBody = null;
    public ?string $responsePreview = null;

    public function __construct(
        private Router $router,
        private ApiModelBuilder $builder,
        private Injector $injector,
        private ?MatchedRoute $route = null,
        private ?Request $request = null
    ) {
    }

    public function get(): void
    {
        $this->description = $this->builder->describe($this->router, $this->apiPathPrefix());
        $this->operations = $this->operations($this->description);
        $this->selectedId = $this->normalizeSelectedId($this->request?->query()->int("id", 0) ?? 0);
    }

    #[PageAction("try")]
    public function submitTryRequest(): void
    {
        $this->description = $this->builder->describe($this->router, $this->apiPathPrefix());
        $this->operations = $this->operations($this->description);
        $this->selectedId = $this->normalizeSelectedId($this->request?->post()->int("id", 0) ?? 0);
        $this->requestBody = $this->request?->post()->string("body", "{}") ?? "{}";

        $operation = $this->selectedOperation();
        if ($operation === null || $this->request === null) {
            return;
        }

        $method = strtoupper($this->request->post()->string("method", $operation->method));
        $url = $this->request->post()->string("url", $operation->path());
        $this->requestUrl = $url;
        $payload = $this->payloadFromBody($this->requestBody);

        if ($payload === null) {
            $this->responsePreview = "Invalid JSON request body.";
            return;
        }

        $targetRequest = $this->requestFromForm($method, $url, $payload, $this->requestBody);
        $apiContext = new InjectionContext();

        try {
            $response = $this->injector->get(Dispatcher::class, $apiContext)->handle($targetRequest);
            $this->responsePreview = $this->formatResponse($response);
        } catch (Throwable $exception) {
            $this->responsePreview = "HTTP 500 Internal Server Error\n\n"
                . $exception::class . ": " . $exception->getMessage();
        }
    }

    public function selectedOperation(): ?ApiOperationDescriptor
    {
        return $this->operations[$this->selectedId] ?? null;
    }

    public function resourcePath(): string
    {
        return $this->options()->resourcePath;
    }

    private function normalizeSelectedId(int $id): int
    {
        if ($this->operations === []) {
            return 0;
        }

        return max(0, min($id, count($this->operations) - 1));
    }

    /**
     * @return ApiOperationDescriptor[]
     */
    private function operations(ApiDescription $description): array
    {
        $operations = [];

        foreach ($description->endpoints as $endpoint) {
            foreach ($endpoint->methods as $method) {
                $operations[] = new ApiOperationDescriptor($method, $endpoint);
            }
        }

        return $operations;
    }

    private function apiPathPrefix(): string
    {
        return $this->options()->apiPathPrefix;
    }

    private function options(): ApiExplorerOptions
    {
        $options = $this->route?->getRouteEntry()->getMetadataOfType(ApiExplorerOptions::class);

        return $options instanceof ApiExplorerOptions
            ? $options
            : new ApiExplorerOptions("/atom/api/resources", "/atom/api/explorer");
    }

    /**
     * @return array<string, mixed>|null
     */
    private function payloadFromBody(string $body): ?array
    {
        if (trim($body) === "") {
            return [];
        }

        $payload = json_decode($body, true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestFromForm(string $method, string $url, array $payload, string $rawBody): Request
    {
        $parts = parse_url($url);
        $path = (string) ($parts["path"] ?? "/");
        $query = [];

        if (isset($parts["query"])) {
            parse_str((string) $parts["query"], $query);
        }

        if ($path === "" || $path[0] !== "/") {
            $path = "/" . $path;
        }

        return new Request(
            $method,
            $path,
            $query,
            $payload,
            $rawBody,
            [],
            [],
            ["Content-Type" => "application/json"]
        );
    }

    private function formatResponse(Response $response): string
    {
        $content = $response->getContent();
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: $content;
        }

        return "HTTP {$response->getStatus()} {$response->getReasonPhrase()}\n\n" . $content;
    }
}
